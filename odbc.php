<?php

require_once __DIR__ . '/vendor/autoload.php';

use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\Schema;
use GenericDatabase\Engine\ODBC\DSN;
use GenericDatabase\Engine\ODBC\ODBC;
use GenericDatabase\Helpers\RegexDateTime;
use GenericDatabase\Engine\ODBC\Statements;

$tests = [
    #YYYY-DD-MM
    "2023-28-02 10:45:30 AM",
    "2023-28-02 10:45:30 PM",
    "2023-28-02 22:45:30 +02:00",
    "2023-28-02 22:45:30 -02:00",
    "2023-28-02 10:45:30",
    "2023-28-02 10:45 AM",
    "2023-28-02 10:45 PM",
    "2023-28-02 22:45 +02:00",
    "2023-28-02 22:45 -02:00",
    "2023-28-02 10:45",
    "2023-28-02",
    "2024-29-02 10:45:30 AM",
    "2024-29-02 10:45:30 PM",
    "2024-29-02 22:45:30 +02:00",
    "2024-29-02 22:45:30 -02:00",
    "2024-29-02 10:45:30",
    "2024-29-02 10:45 AM",
    "2024-29-02 10:45 PM",
    "2024-29-02 22:45 +02:00",
    "2024-29-02 22:45 -02:00",
    "2024-29-02 10:45",
    "2024-29-02",

    // #YYYY-MM-DD
    "2023-02-28 10:45:30 AM",
    "2023-02-28 10:45:30 PM",
    "2023-02-28 22:45:30 +02:00",
    "2023-02-28 22:45:30 -02:00",
    "2023-02-28 10:45:30",
    "2023-02-28 10:45 AM",
    "2023-02-28 10:45 PM",
    "2023-02-28 22:45 +02:00",
    "2023-02-28 22:45 -02:00",
    "2023-02-28 10:45",
    "2023-02-28",
    "2024-02-29 10:45:30 AM",
    "2024-02-29 10:45:30 PM",
    "2024-02-29 22:45:30 +02:00",
    "2024-02-29 22:45:30 -02:00",
    "2024-02-29 10:45:30",
    "2024-02-29 10:45 AM",
    "2024-02-29 10:45 PM",
    "2024-02-29 22:45 +02:00",
    "2024-02-29 22:45 -02:00",
    "2024-02-29 10:45",
    "2024-02-29",

    // #MM-DD-YYYY
    "02-28-2023 10:45:30 AM",
    "02-28-2023 10:45:30 PM",
    "02-28-2023 10:45:30 +02:00",
    "02-28-2023 10:45:30 -02:00",
    "02-28-2023 10:45:30",
    "02-28-2023 10:45 AM",
    "02-28-2023 10:45 PM",
    "02-28-2023 10:45 +02:00",
    "02-28-2023 10:45 -02:00",
    "02-28-2023 10:45",
    "02-28-2023",
    "02-29-2024 10:45:30 AM",
    "02-29-2024 10:45:30 PM",
    "02-29-2024 10:45:30 +02:00",
    "02-29-2024 10:45:30 -02:00",
    "02-29-2024 10:45:30",
    "02-29-2024 10:45 AM",
    "02-29-2024 10:45 PM",
    "02-29-2024 10:45 +02:00",
    "02-29-2024 10:45 -02:00",
    "02-29-2024 10:45",
    "02-29-2024",

    // #DD-MM-YYYY
    "28-02-2023 10:45:30 AM",
    "28-02-2023 10:45:30 PM",
    "28-02-2023 10:45:30 +02:00",
    "28-02-2023 10:45:30 -02:00",
    "28-02-2023 10:45:30",
    "28-02-2023 10:45 AM",
    "28-02-2023 10:45 PM",
    "28-02-2023 10:45 +02:00",
    "28-02-2023 10:45 -02:00",
    "28-02-2023 10:45",
    "28-02-2023",
    "29-02-2024 10:45:30 AM",
    "29-02-2024 10:45:30 PM",
    "29-02-2024 10:45:30 +02:00",
    "29-02-2024 10:45:30 -02:00",
    "29-02-2024 10:45:30",
    "29-02-2024 10:45 AM",
    "29-02-2024 10:45 PM",
    "29-02-2024 10:45 +02:00",
    "29-02-2024 10:45 -02:00",
    "29-02-2024 10:45",
    "29-02-2024",

    #HH:II?(:SS)?(A|P)
    "13:45:30 +03:00",
    "13:45:30 +02:00",
    "10:45:30 AM",
    "10:45:30 PM",
    "13:45:30",
    "13:45 +02:00",
    "13:45 -02:00",
    "10:45 AM",
    "10:45 PM",
    "13:45"
];

// var_dump(DSN::load());
// var_dump(ODBC::getAvailableDrivers());
// var_dump(ODBC::getAvailableAliases());
// var_dump(ODBC::getDriverSettings());
// var_dump(ODBC::getAliasByDriver('oci', null));
// var_dump(ODBC::getDriverSettingsByDriver(ODBC::getAliasByDriver('oci', null)));

// foreach ($tests as $test) {
//     var_dump(RegexDateTime::getPattern($test));
// }

if (extension_loaded('odbc')) {
    try {
        //Text Files
        $dsnText = sprintf(
            "DRIVER=%s;DBQ=%s;CHARSET=%s;Extensions=asc,csv,tab,txt;FMT=Delimited(;);HDR=YES;",
            "{Microsoft " . ((PHP_INT_SIZE === 4) ? "" : "Access") . " Text Driver (*.txt, *.csv)}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\csv",
            "utf8",
        );

        // Excel 97
        $oldExcel = sprintf(
            "DRIVER=%s;DriverId=790;DBQ=%s;DefaultDir=%s;CHARSET=%s;Extensions=xls;ImportMixedTypes=Text;",
            "{Microsoft Excel Driver (*.xls)}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\excel\\DB.xls",
            dirname("F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\excel\\DB.xls"),
            "utf8",
        );

        // Excel 2007
        $newExcel = sprintf(
            "DRIVER=%s;DriverID=1046;DBQ=%s;DefaultDir=%s;CHARSET=%s;Extensions=xls,xlsx,xlsm,xlsb;ImportMixedTypes=Text;",
            "{Microsoft Excel Driver (*.xls, *.xlsx, *.xlsm, *.xlsb)}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\excel\\DB.xlsx",
            dirname("F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\excel\\DB.xlsx"),
            "utf8",
        );

        // Access ACCDB
        $dsnAccdb = sprintf(
            "Driver=%s;DBQ=%s;UID=%s;PWD=%s;CHARSET=%s;ExtendedAnsiSQL=1;",
            "{Microsoft Access Driver (*.mdb, *.accdb)}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\access\\DB.MDB",
            "",
            "",
            "utf8",
        );

        // Access MDB
        $dsnMdb = sprintf(
            "Driver=%s;DBQ=%s;UID=%s;PWD=%s;CHARSET=%s;ExtendedAnsiSQL=1;",
            "{Microsoft Access Driver (*.mdb)}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\access\\DB.MDB",
            "",
            "",
            "utf8",
        );

        // Firebird
        $firebird = sprintf(
            "DRIVER=%s;UID=%s;PWD=%s;DBNAME=%s/%s:%s;CHARSET=%s;AUTOQUOTED=YES;",
            "{Firebird/InterBase(r) driver}",
            "sysdba",
            "masterkey",
            "localhost",
            "3050",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\firebird\\DB.FDB",
            "utf8"
        );

        // SQLite 3
        $sqlite3 = sprintf(
            "DRIVER=%s;Database=%s;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;StepAPI=0;CHARSET=%s",
            "{SQLite3 ODBC Driver}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\sqlite\DB.SQLITE",
            "utf8"
        );

        // Oracle
        $oracle = sprintf(
            "DRIVER=%s;Server=%s:%s/%s;UID=%s;PWD=%s;CHARSET=%s;",
            "{Oracle in instantclient_21_9}", //{Oracle em OraDB21Home1}
            "localhost",
            "1521",
            "xe",
            "hr",
            "masterkey",
            "utf8"
        );

        // $oracle = sprintf(
        //     "Driver=%s;Server=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SID=%s)));Uid=%s;Pwd=%s;CHARSET=%s;",
        //     "{Microsoft ODBC for Oracle}",
        //     "localhost",
        //     "1521",
        //     "xe",
        //     "hr",
        //     "masterkey",
        //     "utf8"
        // );

        // Postgres
        $postgres = sprintf(
            "DRIVER=%s;Server=%s;Port=%s;Database=%s;UID=%s;PWD=%s;CHARSET=%s;",
            "{PostgreSQL ANSI(x64)}",
            "localhost",
            "5432",
            "postgres",
            "postgres",
            "masterkey",
            "utf8"
        );

        // SQLServer
        $sqlserver = sprintf(
            "DRIVER=%s;Server=%s,%s;Database=%s;UID=%s;PWD=%s;Trusted_Connection=YES;CHARSET=%s;",
            "{ODBC Driver 17 for SQL Server}",
            "localhost",
            "1433",
            "demodev",
            "sa",
            "masterkey",
            "utf8"
        );

        // MySQL
        $mysql = sprintf(
            "DRIVER=%s;Server=%s;Port=%s;Database=%s;User=%s;Password=%s;Charset=%s;Option=3;",
            "{MySQL ODBC 8.3 ANSI Driver}",
            "localhost",
            "3306",
            "demodev",
            "root",
            "masterkey",
            "utf8"
        );

        // $result = Schema::structure('F:\Projetos\PHP\PHP-Generic-Database\resources\database\csv', ';');
        // var_dump($result);
        // Schema::write(true);

        $odbcText = odbc_connect($dsnText, "", "");
        // var_dump($odbcText);
        // $resultText = odbc_exec($odbcText, "SELECT id as Codigo, nome as Estado, sigla as UF FROM [estado.csv]");
        // $resultText = odbc_exec($odbcText, "SELECT * FROM [card_sector.csv] WHERE id > 1");
        // var_dump(ODBC::fetchAll($resultText));

        $odbcExcel = odbc_connect($newExcel, "", "");
        // var_dump($odbcExcel);
        // $resultExcel = odbc_exec($odbcExcel, "SELECT id as Codigo, nome as Estado, sigla as UF FROM [estado$] WHERE id > 10");
        // var_dump(ODBC::fetchAll($resultExcel));

        $odbcAccdb = odbc_connect($dsnAccdb, "", "");
        // var_dump($odbcAccdb);
        // $resultAccdb = odbc_exec($odbcAccdb, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
        // var_dump(ODBC::fetchAll($resultAccdb));

        $odbcFb = odbc_connect($firebird, "sysdba", "masterkey");
        // var_dump($odbcFb);
        // $resultFb = odbc_exec($odbcFb, 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "UF" FROM "estado" WHERE "id" > 10');
        // var_dump(ODBC::fetchAll($resultFb));

        $odbcOci = odbc_connect($oracle, "hr", "masterkey");
        // $resultOci = odbc_exec($odbcOci, 'SELECT "id" AS "Codigo", "nome" AS "Estado", "sigla" AS "UF" FROM "estado" WHERE "id" > 10');
        // var_dump(ODBC::fetchAll($resultOci));

        $odbcPg = odbc_connect($postgres, "postgres", "masterkey");
        // var_dump($odbcPg);
        // $resultPg = odbc_exec($odbcPg, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
        // var_dump(ODBC::fetchAll($resultPg));

        $odbcSqlsrv = odbc_connect($sqlserver, "sa", "masterkey");
        // var_dump($odbcSqlsrv);
        // $resultSqlsrv = odbc_exec($odbcSqlsrv, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
        // var_dump(ODBC::fetchAll($resultSqlsrv));

        $odbcMysql = odbc_connect($mysql, "root", "masterkey");
        // var_dump($odbcMysql);
        // $resultMysql = odbc_exec($odbcMysql, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
        // var_dump(ODBC::fetchAll($resultMysql));

        // $pstmt = odbc_prepare($odbcMysql, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
        // $res = odbc_execute($pstmt);

        // $pstmt = odbc_prepare($odbcMysql, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > ?");
        // $res = odbc_execute($pstmt, array("10"));
        // var_dump(ODBC::fetchAll($pstmt));


        $odbcSqlite = odbc_connect($sqlite3, "", "");
        // var_dump($odbcSqlite);
        // $resultSqlite = odbc_exec($odbcSqlite, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
        // var_dump(ODBC::fetchAll($resultSqlite));

        // $ini_array = INI::parseIniFile("F:\\Projetos\\PHP\\PHP-Generic-Database\\odbcinst.ini");
        // var_dump($ini_array);

        // COLUMNS
        // var_dump(ODBC::fetchColumns($odbcText));

        // TABLES
        // var_dump(ODBC::fetchTables($odbcText));

        // COLUMNS
        // var_dump(ODBC::fetchColumns($odbcExcel));

        // TABLES
        // var_dump(ODBC::fetchTables($odbcExcel));

        // COLUMNS
        // var_dump(ODBC::fetchColumns($odbcAccdb));

        // TABLES
        // var_dump(ODBC::fetchTables($odbcAccdb));

        // odbc_close($odbcText);
        // odbc_close($odbcExcel);
        // odbc_close($odbcAccdb);

        // if (!$odbc) {
        //     throw new Exception("Database Connection Error");
        // }
        // echo '<p>ODBC Connected</p>';
    } catch (Exception $e) {
        var_dump($e);
    }
}

// https://www.connectionstrings.com/
// https://stackoverflow.com/questions/18556047/php-odbc-connect-to-access-mdb-file-another-server
// https://gist.github.com/treffynnon/294738
// https://github.com/mpericay/apicollector/blob/master/lib/db/class.odbc.php
// https://snipplr.com/view/24916/php-connection-class-odbc-v1
