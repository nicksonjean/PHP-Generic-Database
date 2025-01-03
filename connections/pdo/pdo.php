<?php

echo '<p style="font-weight: bold;">PDO</p>';

if (extension_loaded('pdo')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'PDO') !== false) {
            return true;
        }
        return false;
    });

    try {
        $db = str_replace('../../', '../', $_ENV['SQLITE_DATABASE']);

        if (!file_exists($db)) {
            throw new Exception('File is not exists in ' . $db);
        }

        $instance = new PDO(
            vsprintf(
                "sqlite:%s",
                [
                    $db
                ]
            )
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'SQLite Connected with PDO');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'SQLite Not Connected with PDO');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The extension PDO not loaded');
}

if (extension_loaded('pdo')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'PDO') !== false) {
            return true;
        }
        return false;
    });

    try {

        $instance = new PDO(
            vsprintf(
                "firebird:dbname=%s/%s:%s;charset=%s",
                [
                    $_ENV['FBIRD_HOST'],
                    $_ENV['FBIRD_PORT'],
                    $_ENV['FBIRD_DATABASE'],
                    $_ENV['FBIRD_CHARSET']
                ]
            ),
            $_ENV['FBIRD_USER'],
            $_ENV['FBIRD_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'Firebird Connected with PDO');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'Firebird Not Connected with PDO');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The extension PDO not loaded');
}

if (extension_loaded('pdo')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'PDO') !== false) {
            return true;
        }
        return false;
    });

    try {

        $instance = new PDO(
            vsprintf(
                "oci:dbname=%s:%s/%s;charset=%s",
                [
                    $_ENV['OCI_HOST'],
                    $_ENV['OCI_PORT'],
                    $_ENV['OCI_DATABASE'],
                    $_ENV['OCI_CHARSET']
                ]
            ),
            $_ENV['OCI_USER'],
            $_ENV['OCI_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'Oracle Connected with PDO');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'Oracle Not Connected with PDO');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The extension PDO not loaded');
}

if (extension_loaded('pdo')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'PDO') !== false) {
            return true;
        }
        return false;
    });

    try {
        $instance = new PDO(
            vsprintf(
                "mysql:host=%s;dbname=%s;port=%s;user=%s;password=%s;charset=%s",
                [
                    $_ENV['MYSQL_HOST'],
                    $_ENV['MYSQL_DATABASE'],
                    $_ENV['MYSQL_PORT'],
                    $_ENV['MYSQL_USER'],
                    $_ENV['MYSQL_PASSWORD'],
                    $_ENV['MYSQL_CHARSET']
                ]
            )
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'MySQL Connected with PDO');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'MySQL Not Connected with PDO');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The extension PDO not loaded');
}

if (extension_loaded('pdo')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'PDO') !== false) {
            return true;
        }
        return false;
    });

    try {
        $instance = new PDO(
            vsprintf(
                "pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s;options='--client_encoding=%s'",
                [
                    $_ENV['PGSQL_HOST'],
                    $_ENV['PGSQL_PORT'],
                    $_ENV['PGSQL_DATABASE'],
                    $_ENV['PGSQL_USER'],
                    $_ENV['PGSQL_PASSWORD'],
                    $_ENV['PGSQL_CHARSET']
                ]
            )
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'PostgreSQL Connected with PDO');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'PostgreSQL Not Connected with PDO');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The extension PDO not loaded');
}

if (extension_loaded('pdo')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'PDO') !== false) {
            return true;
        }
        return false;
    });

    try {
        $instance = new PDO(
            vsprintf(
                "sqlsrv:server=%s,%s;database=%s",
                [
                    $_ENV['SQLSRV_HOST'],
                    $_ENV['SQLSRV_PORT'],
                    $_ENV['SQLSRV_DATABASE'],
                ]
            ),
            $_ENV['SQLSRV_USER'],
            $_ENV['SQLSRV_PASSWORD'],
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'SQL Server Connected with PDO');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'SQL Server Not Connected with PDO');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extension pdo not loaded');
}

echo '<p style="font-weight: bold;">PDO and ODBC</p>';

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'odbc_connect') !== false) {
            return true;
        }
        return false;
    });

    try {

        $db = str_replace('../../', '../', $_ENV['SQLITE_DATABASE']);

        if (!file_exists($db)) {
            throw new Exception('File is not exists in ' . $db);
        }

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={SQLite3};Database=%s",
                [
                    $db,
                ]
            ),
            '',
            ''
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'SQLite Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'SQLite Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extensions pdo and odbc are not loaded');
}

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    try {

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={Firebird/InterBase(r) driver};DBName=%s/%s:%s;Charset=%s",
                [
                    $_ENV['FBIRD_HOST'],
                    $_ENV['FBIRD_PORT'],
                    $_ENV['FBIRD_DATABASE'],
                    $_ENV['FBIRD_CHARSET']
                ]
            ),
            $_ENV['FBIRD_USER'],
            $_ENV['FBIRD_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'Firebird Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'Firebird Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extensions PDO and ODBC are not loaded');
}

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    try {

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={Oracle 21 ODBC driver};DBQ=%s:%s/%s;Charset=%s",
                [
                    $_ENV['OCI_HOST'],
                    $_ENV['OCI_PORT'],
                    $_ENV['OCI_DATABASE'],
                    $_ENV['OCI_CHARSET']
                ]
            ),
            $_ENV['OCI_USER'],
            $_ENV['OCI_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'Oracle Server Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'Oracle Server Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();
} else {
    echo set_message('error', 'The Extensions PDO and ODBC are not loaded');
}

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    try {

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={MySQL ODBC 8.0 Unicode Driver};Server=%s;Port=%s;DBQ=%s;Charset=%s",
                [
                    $_ENV['MYSQL_HOST'],
                    $_ENV['MYSQL_PORT'],
                    $_ENV['MYSQL_DATABASE'],
                    $_ENV['MYSQL_CHARSET']
                ]
            ),
            $_ENV['MYSQL_USER'],
            $_ENV['MYSQL_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'MysQL Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'MysQL Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extensions PDO and ODBC are not loaded');
}

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    try {

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={PostgreSQL Ansi};Server=%s;Port=%s;DBQ=%s;Charset=%s",
                [
                    $_ENV['PGSQL_HOST'],
                    $_ENV['PGSQL_PORT'],
                    $_ENV['PGSQL_DATABASE'],
                    $_ENV['PGSQL_CHARSET']
                ]
            ),
            $_ENV['PGSQL_USER'],
            $_ENV['PGSQL_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'PostgreSQL Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'PostgreSQL Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extensions PDO and ODBC are not loaded');
}

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    try {

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={ODBC Driver 17 for SQL Server};Server=%s;Port=%s;DBQ=%s;Charset=%s",
                [
                    $_ENV['SQLSRV_HOST'],
                    $_ENV['SQLSRV_PORT'],
                    $_ENV['SQLSRV_DATABASE'],
                    $_ENV['SQLSRV_CHARSET']
                ]
            ),
            $_ENV['SQLSRV_USER'],
            $_ENV['SQLSRV_PASSWORD']
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'SQL Server Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'SQL Server Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extensions PDO and ODBC are not loaded');
}

if (extension_loaded('pdo') && extension_loaded('odbc')) {

    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        if (stripos($errstr, 'odbc_connect') !== false) {
            return true;
        }
        return false;
    });

    try {

        $db = str_replace('../../', '../', $_ENV['ACCESS_DATABASE']);

        if (!file_exists($db)) {
            throw new Exception('File is not exists in ' . $db);
        }

        $instance = new PDO(
            vsprintf(
                "odbc:Driver={MDBTools};DBQ=%s",
                [
                    $db,
                ]
            ),
            '',
            ''
        );

        if (!$instance instanceof PDO) {
            throw new Exception("");
        }

        echo set_message('success', 'Access Connected with PDO and ODBC');
        if (check_params('show_objects')) {
            var_dump($instance);
        }

    } catch (Exception $e) {

        echo set_message('error', 'Access Not Connected with PDO and ODBC');
        if (check_params('show_errors')) {
            var_dump($e->getMessage());
        }

    }

    restore_error_handler();

} else {
    echo set_message('error', 'The Extensions pdo and odbc are not loaded');
}
?>
<hr />