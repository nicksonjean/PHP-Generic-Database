<div class="tab-pane fade" id="odbc-access">
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

            $database = str_replace('../../', '../', $_ENV["{$env}_DATABASE"]);

            if (!file_exists($database)) {
                throw new Exception($lang->getLabelVars('file_not_found', ['database' => $database]));
            }

            $instance = odbc_connect(
                vsprintf(
                    "Driver={MDBTools};DBQ=%s",
                    [
                        $database
                    ]
                ),
                '',
                ''
            );
            if (!$instance) {
                throw new Exception($lang->getLabel('connection_fail'));
            }

            echo set_message('success', $lang->getLabelVars('connection_success', ['label' => $label, 'method' => $method]));
            if (check_params('show_objects')) {
                var_dump($instance);
            }

        } catch (Exception $e) {

            echo set_message('warning', $lang->getLabelVars('connection_error', ['label' => $label, 'method' => $method]));
            if (check_params('show_errors')) {
                var_dump($e->getMessage());
            }

            restore_error_handler();

        }

    } else {
        echo set_message('danger', $lang->getLabelVars('extension', ['extension' => $extension]));
    }
    ?>
</div>