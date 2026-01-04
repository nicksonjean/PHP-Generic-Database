<?php
/**
 * Custom directory listing for Nginx
 * Shows only allowed files and directories
 */

// Verificar se estamos realmente na raiz
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = trim($requestPath, '/');

// Se o script sendo executado não for o index.php da raiz, não processar
if (basename($scriptName) !== 'index.php' || dirname($scriptName) !== '/') {
    // Se for um subdiretório, retornar 404 para deixar o Nginx processar normalmente
    if ($requestPath !== '' && $requestPath !== 'index.php') {
        http_response_code(404);
        exit;
    }
}

// Lista de diretórios permitidos
$allowedDirs = ['build', 'connections', 'docs', 'readme', 'samples'];

// Lista de arquivos permitidos
$allowedFiles = ['phpinfo.php'];

// Obter o diretório atual (apenas para a raiz)
$currentDir = '.';
$basePath = __DIR__;

// Listar apenas diretórios e arquivos permitidos na raiz
$items = [];
if (is_dir($basePath)) {
    $files = scandir($basePath);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === 'index.php') {
            continue;
        }
        
        $fullPath = $basePath . '/' . $file;
        $isDir = is_dir($fullPath);
        
        // Verificar se é permitido
        if ($isDir && in_array($file, $allowedDirs)) {
            $items[] = [
                'name' => $file,
                'type' => 'dir',
                'path' => $file,
                'size' => '-',
                'modified' => date('Y-m-d H:i', filemtime($fullPath))
            ];
        } elseif (!$isDir && in_array($file, $allowedFiles)) {
            $items[] = [
                'name' => $file,
                'type' => 'file',
                'path' => $file,
                'size' => formatBytes(filesize($fullPath)),
                'modified' => date('Y-m-d H:i', filemtime($fullPath))
            ];
        }
    }
}

// Função para formatar bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Index of <?php echo htmlspecialchars($currentDir === '.' ? '/' : '/' . $currentDir); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background: #333;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f9f9f9;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .dir::before {
            content: "📁 ";
        }
        .file::before {
            content: "📄 ";
        }
    </style>
</head>
<body>
    <h1>Index of /</h1>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Last Modified</th>
                <th>Size</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="<?php echo $item['type']; ?>">
                    <a href="<?php echo htmlspecialchars($item['path']); ?><?php echo $item['type'] === 'dir' ? '/' : ''; ?>"><?php echo htmlspecialchars($item['name']); ?><?php echo $item['type'] === 'dir' ? '/' : ''; ?></a>
                </td>
                <td><?php echo $item['modified']; ?></td>
                <td><?php echo $item['size']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
