<div class="tab-pane fade" id="pdo-firebird">
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

            $instance = new PDO(
                vsprintf(
                    "firebird:dbname=%s/%s:%s;charset=%s",
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

            echo set_message('success', $lang->getLabelVars('connection_success', ['label' => $label, 'method' => strtoupper($method)]));
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
