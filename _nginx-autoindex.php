<?php
/**
 * Nginx Autoindex Fallback with Filtering
 * Este script funciona como fallback quando index.php não existe
 * e lista apenas os arquivos e diretórios permitidos
 */

// Lista de diretórios permitidos (mesmos do index.php)
$allowedDirs = ['build', 'connections', 'docs', 'readme', 'samples'];

// Lista de arquivos permitidos (mesmos do index.php)
$allowedFiles = ['phpinfo.php'];

// Diretórios que devem ser ocultados
$hiddenDirs = ['trash', 'vendor', 'assets', 'cache', 'resources', 'scripts', 'tests', 'src', 'bin', 'docker', 'adminer', 'patches', '.devcontainer', '.stub'];

// Extensões de arquivos que devem ser ocultados
$hiddenExtensions = ['stub.php', 'md', 'sh', 'bat', 'mmd'];

// Arquivos específicos que devem ser ocultados
$hiddenFiles = ['composer.json', 'composer.lock', 'doctum.php', 'grumphp.yml', 'phpcs.xml', 'phpmd.xml', 'phpstan.neon', 'phpunit.xml', 'docker-compose.yml', 'favicon.ico'];

// Obter o diretório atual
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$currentPath = parse_url($currentPath, PHP_URL_PATH);
$currentPath = trim($currentPath, '/');
$basePath = __DIR__ . ($currentPath ? '/' . $currentPath : '');

// Verificar se o caminho é válido
if (!is_dir($basePath)) {
    http_response_code(404);
    exit('Directory not found');
}

// Listar arquivos e diretórios
$items = [];
$files = scandir($basePath);

foreach ($files as $file) {
    if ($file === '.' || $file === '..' || $file === 'index.php' || $file === 'nginx-autoindex.php') {
        continue;
    }
    
    $fullPath = $basePath . '/' . $file;
    $isDir = is_dir($fullPath);
    
    // Verificar se deve ser ocultado
    $shouldHide = false;
    
    if ($isDir) {
        // Ocultar diretórios na lista de ocultos
        if (in_array($file, $hiddenDirs)) {
            $shouldHide = true;
        }
        // Mostrar apenas diretórios permitidos (se estiver na raiz)
        elseif ($currentPath === '' && !in_array($file, $allowedDirs)) {
            $shouldHide = true;
        }
    } else {
        // Ocultar arquivos com extensões ocultas
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($ext, $hiddenExtensions)) {
            $shouldHide = true;
        }
        // Ocultar arquivos específicos
        elseif (in_array($file, $hiddenFiles)) {
            $shouldHide = true;
        }
        // Ocultar arquivos que começam com ponto
        elseif (strpos($file, '.') === 0) {
            $shouldHide = true;
        }
        // Se estiver na raiz, mostrar apenas arquivos permitidos
        elseif ($currentPath === '' && !in_array($file, $allowedFiles)) {
            $shouldHide = true;
        }
    }
    
    if (!$shouldHide) {
        $items[] = [
            'name' => $file,
            'type' => $isDir ? 'dir' : 'file',
            'path' => ($currentPath ? $currentPath . '/' : '') . $file,
            'size' => $isDir ? '-' : formatBytes(filesize($fullPath)),
            'modified' => date('Y-m-d H:i', filemtime($fullPath))
        ];
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

// Ordenar: diretórios primeiro, depois arquivos, ambos alfabeticamente
usort($items, function($a, $b) {
    if ($a['type'] !== $b['type']) {
        return $a['type'] === 'dir' ? -1 : 1;
    }
    return strcasecmp($a['name'], $b['name']);
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Index of <?php echo htmlspecialchars($currentPath === '' ? '/' : '/' . $currentPath); ?></title>
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
    <h1>Index of <?php echo htmlspecialchars($currentPath === '' ? '/' : '/' . $currentPath); ?></h1>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Last Modified</th>
                <th>Size</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($currentPath !== ''): ?>
            <tr>
                <td class="dir">
                    <a href="<?php echo dirname($_SERVER['REQUEST_URI']) === '/' ? '/' : dirname($_SERVER['REQUEST_URI']); ?>">../</a>
                </td>
                <td>-</td>
                <td>-</td>
            </tr>
            <?php endif; ?>
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
