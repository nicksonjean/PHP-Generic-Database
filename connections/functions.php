<?php
/**
 * Verifica se um parâmetro $_GET existe e possui um valor positivo.
 *
 * @param string $param Nome do parâmetro a ser verificado.
 * @return bool Retorna true se o parâmetro existir e for positivo, false caso contrário.
 */

function check_params($param)
{
    if (isset($_GET[$param])) {
        $value = $_GET[$param];
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }
    return false;
}

function vsprintf_assoc($format, $values)
{
    $formattedString = vsprintf($format, $values);
    $formattedParams = explode(';', $formattedString);

    $assocArray = [];
    foreach ($formattedParams as $param) {
        list($key, $value) = explode('=', $param);
        $assocArray[trim($key)] = trim($value);
    }
    return $assocArray;
}

/**
 * Carrega variáveis de ambiente de um arquivo especificado.
 *
 * @param string $filePath Caminho completo para o arquivo .env.
 * @return bool Retorna true se o arquivo foi carregado com sucesso, false caso contrário.
 */
function load_env_file($filePath)
{
    if (!file_exists($filePath)) {
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
            $value = $matches[1];
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    return true;
}

function set_message($type = 'success', $message = '')
{
    return vsprintf(
        '<p style="color: %s; font-weight: bold;">%s %s</p>',
        [
            $type === 'success' ? 'green' : 'red',
            $type === 'success' ? '✔️' : '❌',
            $message
        ]
    );
}
