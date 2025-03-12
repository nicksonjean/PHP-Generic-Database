<?php

require_once 'vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$sourceDir = $projectRoot . '/src';
$outputFile = $projectRoot . '/diagram.md';
$tempDir = $projectRoot . '/temp';

if (!is_dir($sourceDir)) {
    die("O diretório '$sourceDir' não foi encontrado. Verifique o caminho e tente novamente.\n");
}

if (!is_dir($tempDir)) {
    mkdir($tempDir);
}

class DirectoryMermaidGenerator
{
    private string $rootPath;
    private string $tempDir;
    private array $directoryMap = [];
    private array $rootDirectories = [];
    private array $validChunks = [];

    public function __construct(string $rootPath, string $tempDir)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->tempDir = rtrim($tempDir, DIRECTORY_SEPARATOR);
        $this->validChunks = $this->listFirstLevelDirectories();
    }

    private function listFirstLevelDirectories(): array {
        if (!is_dir($this->rootPath)) {
            die("O caminho especificado não é um diretório.\n");
        }
    
        $directories = array_filter(glob($this->rootPath . DIRECTORY_SEPARATOR .'*'), 'is_dir');
        sort($directories);
    
        return array_map('basename', $directories);
    }

    private function deleteFolder(): void {
        if (!is_dir($this->tempDir)) {
            die("Erro: O caminho especificado não é um diretório.\n");
        }
    
        $files = array_diff(scandir($this->tempDir), ['.', '..']);
    
        foreach ($files as $file) {
            $filePath = $this->tempDir . DIRECTORY_SEPARATOR . $file;
    
            if (is_dir($filePath)) {
                $this->deleteFolder();
            } else {
                if (!unlink($filePath)) {
                    die("Erro ao excluir arquivo: $filePath\n");
                }
            }
        }
    
        if (!rmdir($this->tempDir)) {
            die("Erro ao excluir diretório: $this->tempDir\n");
        }
    }

    private function mergeChunkFiles() {
        $chunkFiles = glob($this->tempDir . DIRECTORY_SEPARATOR . '*.chunk');
        
        if (!$chunkFiles) {
            die("Nenhum arquivo .chunk encontrado no diretório.\n");
        }

        $rootFile = $this->tempDir . DIRECTORY_SEPARATOR . 'root.chunk';
        $otherFiles = array_diff($chunkFiles, [$rootFile]);

        sort($otherFiles);

        $orderedFiles = array_merge([$rootFile], $otherFiles);

        $mergedContent = "flowchart TB" . "\n";
        foreach ($orderedFiles as $file) {
            if (file_exists($file)) {
                $mergedContent .= file_get_contents($file) . "\n";
            }
        }

        $outputFile = dirname($this->rootPath) . DIRECTORY_SEPARATOR  .'flowchart.mmd';
        file_put_contents($outputFile, $mergedContent);
    }

    public function generate(): void
    {
        $this->identifyRootElements();
        $this->generateRootFragments();
        
        foreach ($this->rootDirectories as $directory) {
            $this->processDirectory(
                $this->rootPath . DIRECTORY_SEPARATOR . $directory, 
                $directory, 
                1
            );
        }
        
        $this->generateDirectoryMap();
        $this->generateChunksFromMap();
        $this->mergeChunkFiles();
        $this->deleteFolder();
    }

    private function identifyRootElements(): void
    {
        $items = scandir($this->rootPath);
        $this->rootDirectories = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $this->rootPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath) && in_array($item, $this->validChunks)) {
                $this->rootDirectories[] = $item;
            }
        }

        sort($this->rootDirectories);
    }

    private function generateRootFragments(): void
    {
        $items = scandir($this->rootPath);
        $files = [];
        $directories = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $this->rootPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                if (in_array($item, $this->validChunks)) {
                    $directories[] = $item;
                }
            } else {
                $files[] = $item;
            }
        }

        sort($files);
        sort($directories);

        $rootFragment = "    Root[\"PHP Generic Database\"]\n";

        foreach ($files as $file) {
            $nodeId = $this->sanitizeNodeName(pathinfo($file, PATHINFO_FILENAME));
            $rootFragment .= "    Root --- " . $nodeId . "[\"" . $file . "\"]\n";
        }

        foreach ($directories as $directory) {
            $nodeId = $this->sanitizeNodeName($directory);
            $rootFragment .= "    Root --- " . $nodeId . "[\"" . $directory . "/\"]\n";
        }

        file_put_contents($this->tempDir . '/root.chunk', $rootFragment);
    }

    private function generateDirectoryMap(): void
    {
        file_put_contents($this->tempDir . '/map.json', json_encode($this->directoryMap, JSON_PRETTY_PRINT));
    }

    private function processDirectory(string $directoryPath, string $directoryName, int $level): void
    {
        if (!is_dir($directoryPath)) {
            return;
        }
        
        $items = scandir($directoryPath);
        $files = [];
        $directories = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $directoryPath . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $directories[] = $item;
            } else {
                $files[] = $item;
            }
        }

        sort($files);
        sort($directories);

        foreach ($files as $file) {
            $this->directoryMap[] = [
                'name' => $file,
                'path' => $directoryPath . DIRECTORY_SEPARATOR . $file,
                'level' => $level,
                'type' => 'file',
                'parent' => $directoryName,
            ];
        }

        foreach ($directories as $directory) {
            $this->directoryMap[] = [
                'name' => $directory,
                'path' => $directoryPath . DIRECTORY_SEPARATOR . $directory,
                'level' => $level,
                'type' => 'directory',
                'parent' => $directoryName,
            ];
            $this->processDirectory($directoryPath . DIRECTORY_SEPARATOR . $directory, $directoryName . '/' . $directory, $level + 1);
        }
    }

    private function sanitizeNodeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', $name);
    }

    private function generateChunksFromMap(): void
    {
        $organizedData = $this->organizeDirectoryData();
        
        foreach ($this->validChunks as $rootDir) {
            if (isset($organizedData[$rootDir])) {
                $data = $organizedData[$rootDir];
                $this->generateStandardChunk($rootDir, $data);
            }
        }
    }
    
    private function organizeDirectoryData(): array
    {
        $organizedData = [];
        
        foreach ($this->directoryMap as $item) {
            $parts = explode('/', $item['parent']);
            $rootDir = $parts[0];
            
            if (!isset($organizedData[$rootDir])) {
                $organizedData[$rootDir] = [
                    'rootFiles' => [],
                    'dirs' => [],
                    'nestedStructure' => [],
                    'allFiles' => [],
                    'hasSubdirs' => false
                ];
            }
            
            if ($item['type'] === 'file') {
                $organizedData[$rootDir]['allFiles'][] = $item;
            }
            
            if (count($parts) === 1 && $item['type'] === 'file') {
                $organizedData[$rootDir]['rootFiles'][] = $item['name'];
            } 
            elseif (count($parts) === 1 && $item['type'] === 'directory') {
                $organizedData[$rootDir]['dirs'][$item['name']] = [
                    'files' => [],
                    'subdirs' => []
                ];
                $organizedData[$rootDir]['hasSubdirs'] = true;
            }
            elseif (count($parts) >= 2 && $item['type'] === 'file') {
                $subdir = $parts[1];
                
                if (!isset($organizedData[$rootDir]['dirs'][$subdir])) {
                    $organizedData[$rootDir]['dirs'][$subdir] = [
                        'files' => [],
                        'subdirs' => []
                    ];
                }
                
                if (count($parts) === 2) {
                    $organizedData[$rootDir]['dirs'][$subdir]['files'][] = $item['name'];
                }
                
                $this->addToNestedStructure($organizedData[$rootDir]['nestedStructure'], $parts, $item);
            }
            elseif (count($parts) >= 2 && $item['type'] === 'directory') {
                $this->addToNestedStructure($organizedData[$rootDir]['nestedStructure'], $parts, $item);
            }
        }
        
        foreach ($organizedData as $rootDir => &$data) {
            sort($data['rootFiles']);
            
            foreach ($data['dirs'] as &$dir) {
                sort($dir['files']);
            }
        }
        
        return $organizedData;
    }
    
    private function addToNestedStructure(&$nestedStructure, $pathParts, $item): void
    {
        array_shift($pathParts);
        
        $currentPath = implode('/', $pathParts);
        
        if (!isset($nestedStructure[$currentPath])) {
            $nestedStructure[$currentPath] = [
                'files' => [],
                'type' => $item['type'],
                'parts' => $pathParts,
                'level' => count($pathParts)
            ];
        }
        
        if ($item['type'] === 'file') {
            $nestedStructure[$currentPath]['files'][] = $item['name'];
            sort($nestedStructure[$currentPath]['files']);
        }
    }

    private function generateStandardChunk(string $rootDir, array $data): void
    {
        $content = "";
        
        if (!empty($data['dirs'])) {
            if (!empty($data['rootFiles'])) {
                $content .= "    {$rootDir} --- {$rootDir}Files[\"\n";
                foreach ($data['rootFiles'] as $file) {
                    $content .= "        {$file}\n";
                }
                $content .= "    \"]\n";
            }
            
            $content .= "    {$rootDir} --- {$rootDir}Dir[\"\n";
            foreach ($data['dirs'] as $subdir => $subdirData) {
                $content .= "        {$subdir}/\n";
            }
            $content .= "    \"]\n\n";
            
            foreach ($data['dirs'] as $subdir => $subdirData) {
                $sanitizedSubdir = $this->sanitizeNodeName($subdir);
                
                $content .= "    {$rootDir}Dir --- {$rootDir}{$sanitizedSubdir}[\"{$subdir}:\n";
                
                foreach ($subdirData['files'] as $file) {
                    $content .= "        {$file}\n";
                }
                
                $subDirs = $this->getDirectSubdirs($data['nestedStructure'], $subdir);
                foreach ($subDirs as $dir) {
                    $content .= "        {$dir}/\n";
                }
                
                $content .= "    \"]\n\n";
                
                foreach ($subDirs as $dir) {
                    $dirPath = $subdir . '/' . $dir;
                    $dirSanitized = $this->sanitizeNodeName($dir);
                    
                    $files = $this->getFilesInPath($data['nestedStructure'], $dirPath);
                    $deeperSubDirs = $this->getDirectSubdirs($data['nestedStructure'], $dirPath);
                    
                    if (!empty($files) || !empty($deeperSubDirs)) {
                        $content .= "    {$rootDir}{$sanitizedSubdir} --- {$rootDir}{$sanitizedSubdir}_{$dirSanitized}[\"{$dir}:\n";
                        
                        foreach ($files as $file) {
                            $content .= "        {$file}\n";
                        }
                        
                        foreach ($deeperSubDirs as $deeperDir) {
                            $content .= "        {$deeperDir}/\n";
                        }
                        
                        $content .= "    \"]\n\n";
                        
                        foreach ($deeperSubDirs as $deeperDir) {
                            $deeperPath = $dirPath . '/' . $deeperDir;
                            $deeperSanitized = $this->sanitizeNodeName($deeperDir);
                            
                            $deeperFiles = $this->getFilesInPath($data['nestedStructure'], $deeperPath);
                            $evenDeeperDirs = $this->getDirectSubdirs($data['nestedStructure'], $deeperPath);
                            
                            if (!empty($deeperFiles) || !empty($evenDeeperDirs)) {
                                $content .= "    {$rootDir}{$sanitizedSubdir}_{$dirSanitized} --- {$rootDir}{$sanitizedSubdir}_{$dirSanitized}_{$deeperSanitized}[\"{$deeperDir}:\n";
                                
                                foreach ($deeperFiles as $file) {
                                    $content .= "        {$file}\n";
                                }
                                
                                foreach ($evenDeeperDirs as $evenDeeper) {
                                    $content .= "        {$evenDeeper}/\n";
                                }
                                
                                $content .= "    \"]\n\n";
                            }
                        }
                    }
                }
            }
        } else {
            $content .= "    {$rootDir} --- {$rootDir}Files[\"\n";
            foreach ($data['rootFiles'] as $file) {
                $content .= "        {$file}\n";
            }
            $content .= "    \"]\n";
        }
        
        $content = rtrim($content);
        
        file_put_contents($this->tempDir . '/' . $rootDir . '.chunk', $content);
    }
    
    private function getDirectSubdirs(array $nestedStructure, string $parentPath): array
    {
        $subdirs = [];
        
        foreach ($nestedStructure as $path => $data) {
            if (strpos($path, $parentPath . '/') === 0) {
                $relativePath = substr($path, strlen($parentPath) + 1);
                if (!str_contains($relativePath, '/')) {
                    $subdirs[] = $relativePath;
                }
            }
        }
        
        $subdirs = array_unique($subdirs);
        sort($subdirs);
        
        return $subdirs;
    }
    
    private function getFilesInPath(array $nestedStructure, string $path): array
    {
        foreach ($nestedStructure as $structPath => $data) {
            if ($structPath === $path && isset($data['files'])) {
                return $data['files'];
            }
        }
        
        return [];
    }
}

try {
    $generator = new DirectoryMermaidGenerator($sourceDir, $tempDir);
    $generator->generate();
} catch (Exception $e) {
    die("Erro ao gerar os fragmentos: " . $e->getMessage() . "\n");
}