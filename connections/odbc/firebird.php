<div class="tab-pane fade" id="odbc-firebird">
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

            $instance = odbc_connect(
                vsprintf(
                    "Driver={Firebird/InterBase(r) driver};DBName=%s/%s:%s;Charset=%s",
                    [
                        $_ENV["{$env}_HOST"],
                        $_ENV["{$env}_PORT"],
                        $_ENV["{$env}_DATABASE"],
                        $_ENV["{$env}_CHARSET"]
                    ]
                ),
                $_ENV["{$env}_USER"],
                $_ENV["{$env}_PASSWORD"]
            );

            if (!$instance) {
                throw new Exception($lang->getLabel('connection_fail'));
            }

            echo set_message('success', $lang->getLabelVars('connection_success', ['label' => $label, 'method' => $method]));
            if (check_params('show_objects')) {
                var_dump($instance);
            }

        } catch (Exception $e) {

            echo set_message('error', $lang->getLabelVars('connection_error', ['label' => $label, 'method' => $method]));
            if (check_params('show_errors')) {
                var_dump($e->getMessage());
            }

            restore_error_handler();

        }

    } else {
        echo set_message('error', $lang->getLabelVars('extension', ['extension' => $extension]));
    }
    ?>
</div>
