<?php
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

function buildUrl($params = [])
{
    $currentParams = $_GET;
    $newParams = array_merge($currentParams, $params);
    return '?' . http_build_query($newParams);
}

function set_message($type = 'success', $message = '')
{
    if ($type === 'primary') {
        $label = 'Info';
        $icon = 'info-fill';
    } else if ($type === 'success') {
        $label = ucfirst($type);
        $icon = 'check-circle-fill';
    } else if ($type === 'warning') {
        $label = ucfirst($type);
        $icon = 'exclamation-triangle-fill';
    } else {
        $label = ucfirst($type);
        $icon = 'exclamation-triangle-fill';
    }
    return vsprintf(
        '<div class="alert alert-%s d-flex align-items-center m-0" role="alert">
            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="%s:"><use xlink:href="#%s"/></svg>
            <div>
                %s
            </div>
        </div>',
        [
            $type,
            $label,
            $icon,
            $message
        ]
    );
}
