<?php

require_once __DIR__ . '/vendor/autoload.php';

use GenericDatabase\Helpers\RegexDateTime;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\INI;

class TextFile
{
    private $folderPath;
    private $separator;

    public function __construct($folderPath, $separator = ';')
    {
        $this->folderPath = $folderPath;
        $this->separator = $separator;
    }

    private function allColumnsNull($data)
    {
        foreach ($data as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    return false;
                }
            }
        }
        return true;
    }

    public function parse($filePath)
    {
        $data = [];
        $header = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, $this->separator);
            if (($row = fgetcsv($handle, 0, $this->separator)) !== false) {
                while ($row !== false) {
                    $rowData = [];
                    foreach ($header as $index => $columnName) {
                        $rowData[$columnName] = $row[$index] ?? null;
                    }
                    $data[] = $rowData;
                    $row = fgetcsv($handle, 0, $this->separator);
                }
            } else {
                foreach ($header as $columnName) {
                    $data[] = [$columnName => null];
                }
            }
            fclose($handle);
        }
        return $data;
    }

    private function analyze($data)
    {
        $types = [];
        if ($this->allColumnsNull($data)) {
            foreach ($data as $columnName => $value) {
                $types[array_keys($data[$columnName])[0]] = 'string';
            }
            return $types;
        }
        foreach ($data[0] as $columnName => $value) {
            $types[$columnName] = 'string';
        }
        foreach ($data as $row) {
            foreach ($row as $columnName => $value) {
                if (is_numeric($value) && (int) $value == $value) {
                    if ($types[$columnName] != 'float') {
                        $types[$columnName] = 'integer';
                    }
                } elseif (is_numeric($value) && is_float($value)) {
                    $types[$columnName] = 'float';
                }
            }
        }
        return $types;
    }

    public function structure()
    {
        $files = glob($this->folderPath . '\*.csv');
        $schemas = [];
        foreach ($files as $file) {
            $data = $this->parse($file);
            $types = $this->analyze($data);
            $schema = [];
            foreach ($types as $columnName => $type) {
                $schema[] = ['name' => $columnName, 'type' => $type];
            }
            $schemas[basename($file)] = $schema;
        }
        return $schemas;
    }

    public function schema($overwrite = false)
    {
        $schemaFilePath = $this->folderPath . '\Schema.ini';
        if (!file_exists($schemaFilePath) || $overwrite) {
            $structure = $this->structure();
            $output = '';
            foreach ($structure as $filename => $columns) {
                $output .= "[$filename]\n";
                $output .= "Format=Delimited(;) \n";
                $output .= "ColNameHeader=True\n";
                foreach ($columns as $index => $column) {
                    $regexParts = [
                    "/([\x{00}-\x{7E}]|",
                    "[\x{C2}-\x{DF}][\x{80}-\x{BF}]|",
                    "\x{E0}[\x{A0}-\x{BF}][\x{80}-\x{BF}]|",
                    "[\x{E1}-\x{EC}\x{EE}\{xEF}][\x{80}-\x{BF}]{2}|",
                    "\x{ED}[\x{80}-\x{9F}][\x{80}-\x{BF}]|",
                    "\x{F0}[\x{90}-\x{BF}][\x{80}-\x{BF}]{2}|",
                    "[\x{F1}-\x{F3}][\x{80}-\x{BF}]{3}|",
                    "\x{F4}[\x{80}-\x{8F}][\x{80}-\x{BF}]{2})|",
                    "(.)/s"
                    ];
                    $columnName = preg_replace(implode('', $regexParts), "$1", $column['name']);
                    $output .= 'Col' . ($index + 1) . '="' . $columnName . '"';
                    switch ($column['type']) {
                        case 'string':
                              $output .= " Text\n";
                            break;
                        case 'integer':
                              $output .= " Integer\n";
                            break;
                        case 'date':
                            $output .= " Date\n";
                            break;
                        case 'datetime':
                            $output .= " DateTime\n";
                            break;
                        default:
                            $output .= " Text\n";
                    }
                }
                $output .= "MaxScanRows=0\n";
                $output .= "CharacterSet=ANSI\n";
                $output .= "DateTimeFormat=yyyy-MM-dd HH:nn:ss\n";
            }
            file_put_contents($schemaFilePath, $output);
        }
    }
}

class ODBC
{
  // odbcinst -j

  //https://www.connectionstrings.com/
    const ODBCINST = 'HKEY_LOCAL_MACHINE\SOFTWARE\ODBC\ODBCINST.INI';

    public static function is32bit(): int|bool
    {
        return PHP_INT_SIZE === 4;
    }

    private static function setType(mixed $input): mixed
    {
        return match (true) {
            preg_match('/\b\d+\.0+\b/', $input) => (int) $input,
            preg_match('/\b\d+\.\d*[1-9]+\b/', $input) => (float) $input,
            preg_match('/\b\d+\b/', $input) => (int) $input,
            preg_match('/true|false/i', $input) => (bool) filter_var($input, FILTER_VALIDATE_BOOLEAN),
            preg_match('/\D+/', $input) => (string) $input,
            default => (string) $input,
        };
    }

    public static function fetchArray($res): false|array
    {
        if (!odbc_fetch_row($res)) {
            return false;
        }
        $row = [];
        $numfields = odbc_num_fields($res);
        for ($i = 1; $i <= $numfields; $i++) {
            $result = odbc_result($res, $i);
            if (mb_detect_encoding($result, 'utf8', true) === false) {
                $result_fixed = self::setType(mb_convert_encoding($result, 'utf8', 'ISO-8859-1'));
                $row[odbc_field_name($res, $i)] = $row[$i - 1] = $result_fixed;
            } else {
                $row[odbc_field_name($res, $i)] = $row[$i - 1] = self::setType($result);
            }
        }
        return $row;
    }

    public static function fetchAll($res): false|array
    {
        $rows = [];
        while ($row = self::fetchArray($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function fetchColumns($res): array
    {
        $result = odbc_columns($res);
        $columns = [];
        while ($row = odbc_fetch_array($result)) {
            $columns[] = $row;
        }
        return Arrays::arrayGroupBy($columns, 'TABLE_NAME');
    }

    public static function fetchTables($res): array
    {
        $result = odbc_tables($res);
        $tables = [];
        while (odbc_fetch_row($result)) {
            if (odbc_result($result, "TABLE_TYPE") || odbc_result($result, "SYSTEM TABLE")) {
                $tableName = odbc_result($result, "TABLE_NAME");
                if (strpos($tableName, "MSys") !== 0) {
                    $tables[] = $tableName;
                }
            }
        }
        return $tables;
    }

    public static function getAvailableDrivers(): array
    {
        $reg = Amp\WindowsRegistry\WindowsRegistry::listKeys(self::ODBCINST);
        $output = [];
        foreach ($reg as $value) {
            $output[] = str_replace(self::ODBCINST . '\\', '', $value);
        }

        $except = ['ODBC Core', 'ODBC Drivers', 'ODBC Translators'];
        return Arrays::exceptByValues($output, $except);
    }
}

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

foreach ($tests as $test) {
    var_dump(RegexDateTime::getPattern($test));
}

if (extension_loaded('odbc')) {
    try {
      //Text Files
        $dsnText = sprintf(
            "DRIVER=%s;DBQ=%s;CHARSET=%s;Extensions=asc,csv,tab,txt;FMT=Delimited(;);HDR=YES;",
            "{Microsoft " . ((ODBC::is32bit()) ? "" : "Access") . " Text Driver (*.txt, *.csv)}",
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

      // SQLite 3
        $sqlite3 = sprintf(
            "DRIVER=%s;Database=%s;LongNames=0;Timeout=1000;NoTXN=0;SyncPragma=NORMAL;StepAPI=0;CHARSET=%s",
            "{SQLite3 ODBC Driver}",
            "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\sqlite\DB.SQLITE",
            "utf8"
        );

      // MySQL
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

      // $folderPath = 'F:\Projetos\PHP\PHP-Generic-Database\resources\database\csv';
      // $separator = ';';
      // $textFile = new TextFile($folderPath, $separator);
      // $result = $textFile->structure();
      // var_dump($result);
      // $textFile->schema(true);

        $odbcText = odbc_connect($dsnText, "", "");
      // var_dump($odbcText);
      // $resultText = odbc_exec($odbcText, "SELECT * FROM [estado.csv] WHERE id > 10");
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
      // var_dump($odbcOci);
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

        $odbcSqlite = odbc_connect($sqlite3, "", "");
      // var_dump($odbcSqlite);
      // $resultSqlite = odbc_exec($odbcSqlite, "SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
      // var_dump(ODBC::fetchAll($resultSqlite));

        $ini_array = INI::parseIniFile("F:\\Projetos\\PHP\\PHP-Generic-Database\\odbcinst.ini");
        var_dump($ini_array);

      // var_dump(ODBC::getAvailableDrivers());

      // var_dump(PDO::getAvailableDrivers());

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

// https://stackoverflow.com/questions/18556047/php-odbc-connect-to-access-mdb-file-another-server
// https://gist.github.com/treffynnon/294738
// https://github.com/mpericay/apicollector/blob/master/lib/db/class.odbc.php
// https://snipplr.com/view/24916/php-connection-class-odbc-v1
