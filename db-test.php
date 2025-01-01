<!DOCTYPE html>
<link rel="stylesheet" href="assets/style.css">

<h1>Connecting to Databases</h1>

<?php
if (extension_loaded('pgsql')) {
    try {
        $pghost = (PHP_OS === 'Windows') ? 'localhost' : 'postgres';
        $postgres = pg_connect("host=$pghost port=5432 dbname=demodev user=postgres password=masterkey");
        if (!$postgres) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($postgres);
        echo '<p>PostgreSQL Connected With pg_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

if (extension_loaded('pdo')) {
    try {
        $pghost = (PHP_OS === 'Windows') ? 'localhost' : 'postgres';
        $postgres = new PDO("pgsql:host=$pghost;dbname=demodev;port=5432;user=postgres;password=masterkey;options='--client_encoding=utf8'");
        if (!$postgres instanceof PDO) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($postgres);
        echo '<p>PostgreSQL Connected With PDO</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

if (extension_loaded('odbc')) {
    try {
        $pghost = (PHP_OS === 'Windows') ? 'localhost' : 'postgres';
        $postgres = odbc_connect("Driver={PostgreSQL Ansi};Server=$pghost;Port=5432;DBQ=demodev", "postgres", "masterkey");
        if (!$postgres) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($postgres);
        echo '<p>PostgreSQL Connected With odbc_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
    }

    if (extension_loaded('pdo')) {
        try {
            $pghost = (PHP_OS === 'Windows') ? 'localhost' : 'postgres';
            $postgres = new PDO("odbc:Driver={PostgreSQL Ansi};Server=$pghost;Port=5432;DBQ=demodev", "postgres", "masterkey");
            if (!$postgres instanceof PDO) {
                throw new Exception("Database Connection Error");
            }
            // var_dump($postgres);
            echo '<p>PostgreSQL Connected With PDO and ODBC</p>';
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}
?>

<hr />

<?php
if (extension_loaded('sqlsrv')) {
    try {
        $sqlhost = (PHP_OS === 'Windows') ? 'localhost' : 'sqlsrv';
        $sqlsrv = sqlsrv_connect("$sqlhost,1433", ["Database" => "demodev", "UID" => "sa", "PWD" => "Masterkey@1"]);
        if (!$sqlsrv) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($sqlsrv);
        echo '<p>SQLSrv Connected with sqlsrv_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
        die(print_r(sqlsrv_errors(), true));
    }
}

if (extension_loaded('pdo')) {
    try {
        $sqlhost = (PHP_OS === 'Windows') ? 'localhost' : 'sqlsrv';
        $sqlsrv = new PDO("sqlsrv:server=$sqlhost,1433;database=demodev", "sa", "Masterkey@1");
        if (!$sqlsrv instanceof PDO) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($sqlsrv);
        echo '<p>SQLSrv Connected With PDO</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

if (extension_loaded('odbc')) {
    try {
        $sqlhost = (PHP_OS === 'Windows') ? 'localhost' : 'sqlsrv';
        $sqlsrv = odbc_connect("Driver={ODBC Driver 17 for SQL Server};Server=$sqlhost;Port=1433;DBQ=demodev", "sa", "Masterkey@1");
        if (!$sqlsrv) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($sqlsrv);
        echo '<p>SQLSrv Connected With odbc_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
    }

    if (extension_loaded('pdo')) {
        try {
            $sqlhost = (PHP_OS === 'Windows') ? 'localhost' : 'sqlsrv';
            $sqlsrv = new PDO("odbc:Driver={ODBC Driver 17 for SQL Server};Server=$sqlhost;Port=1433;DBQ=demodev", "sa", "Masterkey@1");
            if (!$sqlsrv instanceof PDO) {
                throw new Exception("Database Connection Error");
            }
            // var_dump($sqlsrv);
            echo '<p>SQLSrv Connected With PDO and ODBC</p>';
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}
?>

<hr />

<?php
if (extension_loaded('mysqli')) {
    try {
        $myhost = (PHP_OS === 'Windows') ? 'localhost' : 'mysql';
        $mysqli = mysqli_connect($myhost, 'root', 'masterkey', 'demodev', 3306);
        if (!$mysqli) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($mysqli);
        echo '<p>MySQL Connected with mysqli_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

if (extension_loaded('pdo')) {
    try {
        $myhost = (PHP_OS === 'Windows') ? 'localhost' : 'mysql';
        $mysql = new PDO("mysql:host=$myhost;dbname=demodev;port=3306;charset=utf8", 'root', 'masterkey');
        if (!$mysql instanceof PDO) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($mysql);
        echo '<p>MySQL Connected With PDO</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

if (extension_loaded('odbc')) {
    try {
        $myhost = (PHP_OS === 'Windows') ? 'localhost' : 'mysql';
        $mysql = odbc_connect("Driver={MySQL ODBC 8.0};Server=$myhost;Port=3306;Database=demodev;Uid=root;Pwd=masterkey;", "root", "masterkey");
        if (!$mysql) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($mysql);
        echo '<p>MySQL Connected With odbc_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
    }

    if (extension_loaded('pdo')) {
        try {
            $myhost = (PHP_OS === 'Windows') ? 'localhost' : 'mysql';
            $mysql = new PDO("odbc:Driver={MySQL ODBC 8.0 Unicode Driver};Server=$myhost;Port=3306;DBQ=demodev", "root", "masterkey");
            if (!$mysql instanceof PDO) {
                throw new Exception("Database Connection Error");
            }
            // var_dump($mysql);
            echo '<p>MySQL Connected With PDO and ODBC</p>';
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}
?>

<hr />

<?php

try {
    $ocihost = (PHP_OS === 'Windows') ? 'localhost' : 'oracle';
    $oracle = oci_connect('hr', 'masterkey', vsprintf('%s:%s/%s', [$ocihost, '1521', 'freepdb1']), 'AL32UTF8');
    if (!$oracle) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($oracle);
    echo '<p>Oracle Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

try {
    $db = './resources/database/sqlite/data/DB.SQLITE';
    /** @var SQLite3|null $sqlite3 */
    $sqlite3 = new SQLite3($db);
    if (!file_exists($db)) {
        die("File is not exists ! " . $db);
    }
    if (!$sqlite3) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($sqlite3);
    echo '<p>SQLite3 Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

if (extension_loaded('interbase')) {
    try {
        /** @var resource|false $ibase */
        $ibase = ibase_connect('firebird:/firebird/data/DB.FDB', 'sysdba', 'masterkey');
        if (!$ibase) {
            throw new Exception("Database Connection Error");
        }
        // var_dump($ibase);
        echo '<p>Interbase Connected</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

try {
    /** @var PDO $pdo */
    $pdo = new PDO('firebird:dbname=firebird:/firebird/data/DB.FDB', 'sysdba', 'masterkey');
    if (!$pdo instanceof PDO) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($pdo);
    echo '<p>PDO Firebird Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

try {
    $ocihost = (PHP_OS === 'Windows') ? 'localhost' : 'oracle';
    /** @var PDO $pdo */
    $pdo = new PDO("oci:dbname=$ocihost:1521/freepdb1", 'hr', 'masterkey');
    if (!$pdo instanceof PDO) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($pdo);
    echo '<p>PDO OCI Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

?>

<hr />

<?php
if (extension_loaded('odbc')) {
    $driver = (PHP_OS === 'Windows') ? "Microsoft Access Driver (*.mdb, *.accdb)" : "MDBTools";
    $db = (PHP_OS === 'Windows') ? "\\resources\\database\\access\\DB.MDB" : "./resources/database/access/DB.MDB";
    try {
        $dsn_odbc = sprintf("DRIVER={%s};charset=%s;DBQ=%s;Uid=%s;Pwd=%s;", $driver, "utf8", $db, "", "");
        $odbc = odbc_connect($dsn_odbc, "", "");
        if ($odbc === false) {
            throw new Exception("Database Connection Error");
        }
        echo '<p>Access Connected with odbc_connect</p>';
    } catch (Exception $e) {
        var_dump($e);
    }

    if (extension_loaded('pdo')) {
        try {
            $dsn_pdo = sprintf("odbc:DRIVER={%s};charset=%s;DBQ=%s;Uid=%s;Pwd=%s;", $driver, "utf8", $db, "", "");
            $odbc = new PDO($dsn_pdo, "", "");
            if (!$odbc instanceof PDO) {
                throw new Exception("Database Connection Error");
            }
            echo '<p>Access Connected PDO and ODBC</p>';
        } catch (Exception $e) {
            var_dump($e);
        }
    }
}

// foreach (PDO::getAvailableDrivers() as $driver) {
//     echo $driver . '<br />';
// }

// https://gist.github.com/amirkdv/9672857
// https://stackoverflow.com/questions/61813881/how-to-access-mdb-data-source-in-php-linux

// https://stackoverflow.com/questions/18556047/php-odbc-connect-to-access-mdb-file-another-server
// https://stackoverflow.com/questions/63707927/how-to-connect-to-microsoft-access-database-from-another-computer-through-odbc-c


// https://gist.github.com/treffynnon/294738
// https://github.com/mpericay/apicollector/blob/master/lib/db/class.odbc.php
// https://snipplr.com/view/24916/php-connection-class-odbc-v1
