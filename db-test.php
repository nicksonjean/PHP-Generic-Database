<!DOCTYPE html>
<link rel="stylesheet" href="assets/style.css">

<h1>Connecting to Databases</h1>

<?php
try {
    $pghost = (PHP_OS === 'Windows') ? 'localhost' : 'postgres';
    $postgres = pg_connect("host=$pghost port=5432 dbname=demodev user=postgres password=masterkey");
    if (!$postgres) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($postgres);
    echo '<p>PostgreSQL Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

try {
    $myhost = (PHP_OS === 'Windows') ? 'localhost' : 'mysql';
    $mysqli = mysqli_connect($myhost, 'root', 'masterkey', 'demodev', 3306);
    if (!$mysqli) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($mysqli);
    echo '<p>MySQLi Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

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

try {
    $sqlhost = (PHP_OS === 'Windows') ? 'localhost' : 'sqlsrv';
    $sqlsrv = sqlsrv_connect(
        "$sqlhost,1433",
        ["Database" => "demodev", "UID" => "sa", "PWD" => "Masterkey@1", "Encrypt" => false, "TrustServerCertificate" => false]
    );
    if (!$sqlsrv) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($sqlsrv);
    echo '<p>SQLSrv Connected</p>';
} catch (Exception $e) {
    var_dump($e);
    die(print_r(sqlsrv_errors(), true));
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
    if (!$pdo) {
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
    if (!$pdo) {
        throw new Exception("Database Connection Error");
    }
    // var_dump($pdo);
    echo '<p>PDO OCI Connected</p>';
} catch (Exception $e) {
    var_dump($e);
}

if (extension_loaded('odbc')) {
    try {
        switch (PHP_OS) {
            case 'Windows':
                $driver = "{Microsoft Access Driver (*.mdb, *.accdb)}";
                $db = "\\resources\\database\\access\\DB.MDB";
                break;
            case 'Linux':
                $driver = 'MDBTools';
                $db = "./resources/database/access/DB.MDB";
                break;
            default:
                exit("Don't know about this OS");
        }
        $charset = "UTF-8";
        $user = "";
        $pass = "";
        if (!file_exists($db)) {
            die("File is not exists ! " . $db);
        }
        $dsn = sprintf(
            "odbc:DRIVER=%s;charset=%s;DBQ=%s;Uid=%s;Pwd=%s;",
            $driver,
            $charset,
            $db,
            $user,
            $pass
        );
        // $odbc = new PDO($dsn);
        $odbc = odbc_connect("DRIVER=" . $driver . ";charset=UTF-8;DBQ=" . $db, $user, $pass);
        // var_dump($odbc);
        if (!$odbc) {
            throw new Exception("Database Connection Error");
        }
        echo '<p>ODBC Connected</p>';
    } catch (Exception $e) {
        var_dump($e);
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
