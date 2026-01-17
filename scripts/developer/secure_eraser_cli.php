<?php

/**
 * Secure Eraser CLI - Remove arquivos e diretórios de forma segura
 * 
 * Este script permite excluir arquivos individuais ou diretórios completos
 * (incluindo subdiretórios e múltiplos arquivos) de forma segura usando PHP nativo.
 * 
 * Uso:
 *   php scripts/developer/secure_eraser_cli.php --dest=/path/to/file
 *   php scripts/developer/secure_eraser_cli.php --dest=/path/to/directory
 * 
 * @package PHP-Generic-Database
 * @subpackage Scripts
 * @category Developer Tools
 */

declare(strict_types=1);

/**
 * Remove um diretório e todo seu conteúdo recursivamente
 * 
 * @param string $dir Caminho do diretório a ser removido
 * @return bool True se removido com sucesso, False caso contrário
 */
function removeDirectory(string $dir): bool
{
    if (!is_dir($dir) || !is_readable($dir)) {
        return false;
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }

        return @rmdir($dir);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Remove um arquivo individual
 * 
 * @param string $file Caminho do arquivo a ser removido
 * @return bool True se removido com sucesso, False caso contrário
 */
function removeFile(string $file): bool
{
    if (!is_file($file) || !is_readable($file)) {
        return false;
    }

    try {
        return @unlink($file);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Normaliza o caminho para o separador correto do sistema
 * 
 * @param string $path Caminho a ser normalizado
 * @return string Caminho normalizado
 */
function normalizePath(string $path): string
{
    // Remove barra final
    $path = rtrim($path, '/\\');
    
    // Substitui separadores mistos pelo separador do sistema
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    
    return $path;
}

// Parse dos argumentos da linha de comando
$args = [];
for ($i = 1; $i < count($argv); $i++) {
    if (strpos($argv[$i], '=') !== false) {
        [$key, $value] = explode('=', $argv[$i], 2);
        $args[$key] = $value;
    } else {
        $args[$argv[$i]] = true;
    }
}

// Verifica se o parâmetro --dest foi fornecido
if (!isset($args['--dest'])) {
    fwrite(STDERR, "ERRO: Parâmetro --dest é obrigatório.\n");
    fwrite(STDERR, "Uso: php secure_eraser_cli.php --dest=/caminho/para/arquivo ou /caminho/para/diretorio\n");
    exit(1);
}

$destination = normalizePath($args['--dest']);

// Verifica se o caminho existe
if (!file_exists($destination)) {
    // Não existe, consideramos sucesso (já está removido)
    exit(0);
}

// Remove arquivo ou diretório
$success = false;
if (is_dir($destination)) {
    $success = removeDirectory($destination);
} elseif (is_file($destination)) {
    $success = removeFile($destination);
} else {
    fwrite(STDERR, "ERRO: Caminho existe mas não é um arquivo ou diretório válido: $destination\n");
    exit(1);
}

// Retorna código de saída baseado no sucesso
exit($success ? 0 : 1);
