<div class="tab-pane fade" id="native-postgresql">
    <?php

    $var = Autoloader::getLoadedVariables();

    $extension = $var['extension'] ?? '';
    $env = $var['env'] ?? '';
    $label = $var['label'] ?? '';
    $method = $var['method'] ?? '';
    $lang = $var['lang'] ?? '';

    if (extension_loaded($extension)) {

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($method) {
            if (stripos($errstr, $method) !== false) {
                return true;
            }
            return false;
        });

        try {

            $instance = pg_connect(
                vsprintf(
                    "host=%s port=%s dbname=%s user=%s password=%s options='--client_encoding=%s'",
                    [
                        $_ENV["{$env}_HOST"],
                        $_ENV["{$env}_PORT"],
                        $_ENV["{$env}_DATABASE"],
                        $_ENV["{$env}_USERNAME"],
                        $_ENV["{$env}_PASSWORD"],
                        $_ENV["{$env}_CHARSET"]
                    ]
                )
            );

            if (!$instance instanceof PgSql\Connection) {
                throw new Exception($lang->getLabel('connection_fail'));
            }

            echo set_message('success', $lang->getLabelVars('connection_success', ['label' => $label, 'method' => $method]));
            if (filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN)) {
                var_dump($instance);
            }

        } catch (Exception $e) {

            echo set_message('warning', $lang->getLabelVars('connection_error', ['label' => $label, 'method' => $method]));
            if (filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN)) {
                var_dump($e->getMessage());
            }

            restore_error_handler();

        }

    } else {
        echo set_message('danger', $lang->getLabelVars('extension', ['extension' => $extension]));
    }
    ?>
</div>
