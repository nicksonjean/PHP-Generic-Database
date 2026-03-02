<?php
/**
 * Funções auxiliares para o Wizard Connection
 *
 * @param string $format Formato da string a ser formatada.
 * @param array $values Valores a serem formatados.
 * @return array Retorna um array associativo com os valores formatados.
 *
 * @example
 * $values = ['name' => 'John', 'age' => 30];
 * $format = 'Hello %s, you are %d years old';
 * $result = vsprintf_assoc($format, $values);
 * print_r($result);
 * // Output: Array ( [name] => John [age] => 30 )
 */
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
 *
 * @example
 * $filePath = 'path/to/env/file.env';
 * $result = load_env_file($filePath);
 * print_r($result);
 * // Output: true
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

/**
 * Constrói uma URL com os parâmetros passados.
 *
 * @param array $params Parâmetros a serem adicionados à URL.
 * @return string Retorna a URL construída.
 *
 * @example
 * $params = ['name' => 'John', 'age' => 30];
 * $url = buildUrl($params);
 * print_r($url);
 * // Output: ?name=John&age=30
 */
function buildUrl($params = [])
{
    $currentParams = $_GET;
    $newParams = array_merge($currentParams, $params);
    return '?' . http_build_query($newParams);
}

/**
 * Define uma mensagem de alerta.
 *
 * @param string $type Tipo da mensagem.
 * @param string $message Mensagem a ser exibida.
 * @return string Retorna a mensagem formatada.
 *
 * @example
 * $type = 'success';
 * $message = 'Connection created successfully';
 * $message = set_message($type, $message);
 * print_r($message);
 * // Output: <div class="alert alert-success d-flex align-items-center m-0" role="alert">
 * //            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Success:"><use xlink:href="#check-circle-fill"/></svg>
 * //            <div>
 * //                Connection created successfully
 * //            </div>
 * //        </div>
 */
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

// ── TOML helpers ─────────────────────────────────────────────────────────────

/**
 * Formata um par chave/valor no estilo TOML.
 *
 * @param string $key   Nome da chave.
 * @param mixed  $value Valor a formatar (bool, int, float ou string).
 * @return string Linha TOML formatada.
 */
function _toml_format_value(string $key, $value): string
{
    if (is_bool($value)) {
        return "$key = " . ($value ? 'true' : 'false');
    }
    if (is_int($value) || is_float($value)) {
        return "$key = $value";
    }
    $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value);
    return "$key = \"$escaped\"";
}

/**
 * Lê um arquivo TOML simples (chave = valor, com suporte a seções [section]).
 * Suporta strings com aspas simples ou duplas, booleanos e números.
 *
 * @param string $filePath Caminho para o arquivo .toml.
 * @return array Array associativo com os valores lidos (seções como sub-arrays).
 *
 * @example
 * $data = read_toml_file('/path/to/settings.toml');
 * // $data['queries_page_size'] => 10
 */
function read_toml_file(string $filePath): array
{
    if (!file_exists($filePath)) {
        return [];
    }

    $result = [];
    $currentSection = null;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Section header: [section_name]
        if (preg_match('/^\[([^\]]+)\]$/', $line, $m)) {
            $currentSection = trim($m[1]);
            if (!isset($result[$currentSection])) {
                $result[$currentSection] = [];
            }
            continue;
        }

        // Key = value
        if (strpos($line, '=') !== false) {
            [$key, $rawValue] = explode('=', $line, 2);
            $key      = trim($key);
            $rawValue = trim($rawValue);

            // Remove inline comment (outside of quotes)
            if (!preg_match('/^".*"$/', $rawValue) && !preg_match("/^'.*'$/", $rawValue)) {
                $rawValue = trim(preg_replace('/#.*$/', '', $rawValue));
            }

            // Parse typed values
            if (strtolower($rawValue) === 'true') {
                $value = true;
            } elseif (strtolower($rawValue) === 'false') {
                $value = false;
            } elseif (preg_match('/^"(.*)"$/s', $rawValue, $m)) {
                $value = str_replace(['\\"', '\\\\'], ['"', '\\'], $m[1]);
            } elseif (preg_match("/^'(.*)'$/s", $rawValue, $m)) {
                $value = $m[1];
            } elseif (is_numeric($rawValue)) {
                $value = strpos($rawValue, '.') !== false ? (float) $rawValue : (int) $rawValue;
            } else {
                $value = $rawValue;
            }

            if ($currentSection !== null) {
                $result[$currentSection][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
    }

    return $result;
}

/**
 * Escreve um array associativo em um arquivo TOML simples.
 * Arrays aninhados são escritos como seções [section].
 *
 * @param string $filePath Caminho para o arquivo .toml a criar/sobrescrever.
 * @param array  $data     Dados a persistir.
 * @return bool true em caso de sucesso, false em caso de falha.
 *
 * @example
 * write_toml_file('/path/to/settings.toml', ['queries_page_size' => 10]);
 */
function write_toml_file(string $filePath, array $data): bool
{
    $lines = [
        '# Wizard Configuration File',
        '# Gerado automaticamente — não edite manualmente',
        '',
    ];

    $sections = [];

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sections[$key] = $value;
        } else {
            $lines[] = _toml_format_value((string) $key, $value);
        }
    }

    foreach ($sections as $section => $entries) {
        $lines[] = '';
        $lines[] = "[$section]";
        foreach ($entries as $k => $v) {
            $lines[] = _toml_format_value((string) $k, $v);
        }
    }

    $lines[] = '';
    return file_put_contents($filePath, implode("\n", $lines)) !== false;
}

// ── End TOML helpers ─────────────────────────────────────────────────────────
