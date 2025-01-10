<div class="tab-pane fade" id="pdo-odbc-postgresql">
    <?php

    $var = Autoloader::getLoadedVariables();

    $extensions = $var['extensions'] ?? '';
    $env = $var['env'] ?? '';
    $label = $var['label'] ?? '';
    $method = $var['method'] ?? '';
    $lang = $var['lang'] ?? '';

    if (extension_loaded($extensions[0]) && extension_loaded($extensions[1])) {

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($method) {
            if (stripos($errstr, $method) !== false) {
                return true;
            }
            return false;
        });

        try {

            $instance = new PDO(
                vsprintf(
                    "odbc:Driver={PostgreSQL Ansi};Server=%s;Port=%s;DBQ=%s;Charset=%s",
                    [
                        $_ENV["{$env}_HOST"],
                        $_ENV["{$env}_PORT"],
                        $_ENV["{$env}_DATABASE"],
                        $_ENV["{$env}_CHARSET"]
                    ]
                ),
                $_ENV["{$env}_USERNAME"],
                $_ENV["{$env}_PASSWORD"]
            );

            if (!$instance instanceof PDO) {
                throw new Exception($lang->getLabel('connection_fail'));
            }

            echo set_message('success', $lang->getLabelVars('connections_success', ['label' => $label, 'pdo' => strtoupper($extensions[0]), 'odbc' => strtoupper($extensions[1])]));
            if (filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN)) {
                var_dump($instance);
            }

        } catch (Exception $e) {

            echo set_message('warning', $lang->getLabelVars('connections_error', ['label' => $label, 'pdo' => strtoupper($extensions[0]), 'odbc' => strtoupper($extensions[1])]));
            if (filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN)) {
                var_dump($e->getMessage());
            }

            restore_error_handler();

        }

    } else {
        echo set_message('danger', $lang->getLabelVars('extensions', ['pdo' => strtoupper($extensions[0]), 'odbc' => strtoupper($extensions[1])]));
    }
    ?>
</div>
