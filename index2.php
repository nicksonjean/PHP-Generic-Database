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
                'modified' => filemtime($fullPath)
            ];
        } elseif (!$isDir && in_array($file, $allowedFiles)) {
            $items[] = [
                'name' => $file,
                'type' => 'file',
                'path' => $file,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath)
            ];
        }
    }
}

// Função para formatar bytes no estilo Nginx
function formatBytesNginx($bytes) {
    if ($bytes == 0) return '-';
    $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 1) . ' ' . $units[$pow];
}

// Função para formatar data no estilo Nginx
function formatDateNginx($timestamp) {
    return date('Y-M-d H:i', $timestamp);
}

// Obter informações do servidor
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

// Para o server name, tentar HTTP_HOST primeiro (mais confiável), depois SERVER_NAME
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';

// Se HTTP_HOST existir e não estiver vazio, usar ele
if (!empty($httpHost)) {
    // Remover a porta se vier junto no HTTP_HOST
    $hostParts = explode(':', $httpHost);
    $serverName = $hostParts[0];
    // Se a porta vier no HTTP_HOST, usar ela
    if (isset($hostParts[1])) {
        $serverPort = $hostParts[1];
    } else {
        $serverPort = $_SERVER['SERVER_PORT'] ?? '80';
    }
} else {
    // Fallback para SERVER_NAME se não for underscore
    if ($serverName === '_') {
        $serverName = 'localhost';
    }
    $serverPort = $_SERVER['SERVER_PORT'] ?? '80';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width">
<style type="text/css">
body,html {
    background:#fff;
    font-family:"Bitstream Vera Sans","Lucida Grande","Lucida Sans Unicode",Lucidux,Verdana,Lucida,sans-serif;
}
tr:nth-child(even) {
    background:#f4f4f4;
}
th,td {
    padding:0.1em 0.5em;
}
th {
    text-align:left;
    font-weight:bold;
    background:#eee;
    border-bottom:1px solid #aaa;
}
#list {
    border:1px solid #aaa;
    width:100%;
}
a {
    color:#a33;
}
a:hover {
    color:#e33;
}
</style>
<title>Index of /</title>
</head>
<body>
<h1>Index of /</h1>
<table id="list">
<thead>
<tr>
<th style="width:55%"><a href="?C=N&amp;O=A">File Name</a>&nbsp;<a href="?C=N&amp;O=D">&nbsp;&darr;&nbsp;</a></th>
<th style="width:20%"><a href="?C=S&amp;O=A">File Size</a>&nbsp;<a href="?C=S&amp;O=D">&nbsp;&darr;&nbsp;</a></th>
<th style="width:25%"><a href="?C=M&amp;O=A">Date</a>&nbsp;<a href="?C=M&amp;O=D">&nbsp;&darr;&nbsp;</a></th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $item): ?>
<tr>
<td class="link"><a href="<?php echo htmlspecialchars($item['path']); ?><?php echo $item['type'] === 'dir' ? '/' : ''; ?>" title="<?php echo htmlspecialchars($item['name']); ?>"><?php echo htmlspecialchars($item['name']); ?><?php echo $item['type'] === 'dir' ? '/' : ''; ?></a></td>
<td class="size"><?php echo $item['type'] === 'dir' ? '-' : formatBytesNginx($item['size']); ?></td>
<td class="date"><?php echo formatDateNginx($item['modified']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>