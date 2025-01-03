<?php

$var = getVars();

$extension = $var['extension'] ?? '';
$env = $var['env'] ?? '';
$label = $var['label'] ?? '';

if (extension_loaded($extension)) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'sqlsrv_connect') !== false) {
            return true;
        }
        return false;
    });

    try {

        $instance = sqlsrv_connect(
            vsprintf(
                "%s,%s",
                [
                    $_ENV[$env . '_HOST'],
                    $_ENV[$env . '_PORT']
                ]
            ),
            vsprintf_assoc(
                'Database=%s;UID=%s;PWD=%s;CharacterSet=%s',
                [
                    $_ENV[$env . '_DATABASE'],
                    $_ENV[$env . '_USER'],
                    $_ENV[$env . '_PASSWORD'],
                    $_ENV[$env . '_CHARSET'] == 'utf8' ? 'UTF-8' : $_ENV[$env . '_CHARSET']
                ]
            )
        );

        if (!$instance) {
            throw new Exception('Connection failed');
        }

        echo set_message('success', $label . ' Connected with sqlsrv_connect');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', $label . ' Not Connected with sqlsrv_connect');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'Extension ' . $extension . ' not loaded');
}
?>
<hr />
