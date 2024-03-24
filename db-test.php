<!DOCTYPE html>
<link rel="stylesheet" href="assets/style.css">

<h1>Connecting to Databases</h1>

<?php

// try {
//   $postgres = pg_connect("host=postgres port=5432 dbname=postgres user=postgres password=masterkey");
//   if (!$postgres) {
//     throw new Exception("Database Connection Error");
//   }
//   // var_dump($postgres);
//   echo '<p>PostgreSQL Connected</p>';
// } catch (Exception $e) {
//   var_dump($e);
// }

// try {
//   $mysqli = mysqli_connect('mysql', 'mysql', 'masterkey', 'mysql', 3306);
//   if (!$mysqli) {
//     throw new Exception("Database Connection Error");
//   }
//   // var_dump($mysqli);
//   echo '<p>MySQLi Connected</p>';
// } catch (Exception $e) {
//   var_dump($e);
// }

// try {
//   $sqlsrv = sqlsrv_connect(
//     "sqlsrv,1433",
//     ["Database" => "msdb", "UID" => "sa", "PWD" => "20Z£?2@Z§!", "Encrypt" => false, "TrustServerCertificate" => false]
//   );
//   if (!$sqlsrv) {
//     throw new Exception("Database Connection Error");
//   }
//   // var_dump($sqlsrv);
//   echo '<p>SQLSrv Connected</p>';
// } catch (Exception $e) {
//   var_dump($e);
// }

// try {
//   $dsn = vsprintf('%s:%s/%s', ['oracle', '1521', 'xe']);
//   $oracle = oci_connect('system', '20Z£?2@Z§!', $dsn, 'AL32UTF8');
//   if (!$oracle) {
//     throw new Exception("Database Connection Error");
//   }
//   // var_dump($oracle);
//   echo '<p>Oracle Connected</p>';
// } catch (Exception $e) {
//   var_dump($e);
// }

// try {
//   $sqlite3 = new SQLite3('./resources/DB.SQLITE');
//   if (!$sqlite3) {
//     throw new Exception("Database Connection Error");
//   }
//   // var_dump($sqlite3);
//   echo '<p>SQLite3 Connected</p>';
// } catch (Exception $e) {
//   var_dump($e);
// }

// if (extension_loaded('interbase')) {
//   try {
//     $ibase = ibase_connect('firebird:/firebird/data/DB.FDB', 'sysdba', 'masterkey');
//     if (!$ibase) {
//       throw new Exception("Database Connection Error");
//     }
//     var_dump($ibase);
//     echo '<p>Interbase Connected</p>';
//   } catch (Exception $e) {
//     var_dump($e);
//   }
// }

// try {
//   $pdo = new PDO('firebird:dbname=firebird:/firebird/data/DB.FDB', 'SYSDBA', 'masterkey');
//   if (!$pdo) {
//     throw new Exception("Database Connection Error");
//   }
//   // var_dump($pdo);
//   echo '<p>PDO Firebird Connected</p>';
// } catch (Exception $e) {
//   var_dump($e);
// }

if (extension_loaded('odbc')) {
  try {
    $driver = "{Microsoft Access Driver (*.mdb, *.accdb)}";
    $db = "/var/www/html/resources/database/access/DB.MDB";
    // $db = "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\DB.MDB";
    $charset = "UTF-8";
    $user = "";
    $pass = "";
    // if (!file_exists($db)) {
    //     die("File is not exists ! " . $db);
    // }
    $dsn = sprintf(
      "odbc:DRIVER=%s;charset=%s;Dbq=%s;Uid=%s;Pwd=%s;",
      $driver,
      $charset,
      $db,
      $user,
      $pass
    );
    // var_dump($dsn);
    $odbc = new PDO($dsn);
    // $odbc = new PDO("odbc:DRIVER=" . $driver . ";charset=UTF-8; Dbq=" . $db . "; Uid=" . $user . "; Pwd=" . $pass . ";");
    // $odbc = odbc_connect("DRIVER=" . $driver . ";charset=UTF-8;Dbq=" . $db, $user, $pass);
    // var_dump($odbc);
    // if (!$odbc) {
    //   throw new Exception("Database Connection Error");
    // }
    // echo '<p>ODBC Connected</p>';
  } catch (Exception $e) {
    var_dump($e);
  }
}

// https://stackoverflow.com/questions/18556047/php-odbc-connect-to-access-mdb-file-another-server
// https://stackoverflow.com/questions/63707927/how-to-connect-to-microsoft-access-database-from-another-computer-through-odbc-c


// https://gist.github.com/treffynnon/294738
// https://github.com/mpericay/apicollector/blob/master/lib/db/class.odbc.php
// https://snipplr.com/view/24916/php-connection-class-odbc-v1
