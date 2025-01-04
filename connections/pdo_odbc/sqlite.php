<div class="tab-pane fade" id="pdo-odbc-sqlite">
    <?php

    $var = getVars();

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

            $database = str_replace('../../', '../', $_ENV["{$env}_DATABASE"]);

            if (!file_exists($database)) {
                throw new Exception($lang->getLabelVars('file_not_found', ['database' => $database]));
            }

            $instance = new PDO(
                vsprintf(
                    "odbc:Driver={SQLite3};Database=%s",
                    [
                        $database,
                    ]
                ),
                '',
                ''
            );

            if (!$instance instanceof PDO) {
                throw new Exception($lang->getLabel('connection_fail'));
            }

            echo set_message('success', $lang->getLabelVars('connections_success', ['label' => $label, 'pdo' => strtoupper($extensions[0]), 'odbc' => strtoupper($extensions[1])]));
            if (check_params('show_objects')) {
                var_dump($instance);
            }

        } catch (Exception $e) {

            echo set_message('error', $lang->getLabelVars('connections_error', ['label' => $label, 'pdo' => strtoupper($extensions[0]), 'odbc' => strtoupper($extensions[1])]));
            if (check_params('show_errors')) {
                var_dump($e->getMessage());
            }

            restore_error_handler();

        }

    } else {
        echo set_message('error', $lang->getLabelVars('extensions', ['pdo' => strtoupper($extensions[0]), 'odbc' => strtoupper($extensions[1])]));
    }
    ?>
</div>
