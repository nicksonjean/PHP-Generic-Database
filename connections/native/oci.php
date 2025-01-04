<div class="tab-pane fade" id="native-oracle">
    <?php

    $var = getVars();

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

            $instance = oci_connect(
                $_ENV["{$env}_USER"],
                $_ENV["{$env}_PASSWORD"],
                vsprintf('%s:%s/%s', [
                    $_ENV["{$env}_HOST"],
                    $_ENV["{$env}_PORT"],
                    $_ENV["{$env}_DATABASE"]
                ]),
                $_ENV["{$env}_CHARSET"]
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
