<!DOCTYPE html>
<link rel="stylesheet" href="assets/style.css">

<h1>Connecting to Databases</h1>

<?php
require_once './functions.php';
require_once './autoload.php';
if (!load_env_file(dirname(__DIR__) . '/.env')) {
    throw new Exception('Falha ao carregar o arquivo .env.');
}

Autoloader::load(
    ['mysql', ['mysql'], ['label' => 'MySQL', 'extension' => 'mysqli', 'env' => 'MYSQL']],
    ['pgsql', ['pgsql'], ['label' => 'PostgreSQL', 'extension' => 'pgsql', 'env' => 'PGSQL']],
    ['sqlsrv', ['sqlsrv'], ['label' => 'SQL Server', 'extension' => 'sqlsrv', 'env' => 'SQLSRV']],
    ['oci', ['oci'], ['label' => 'Oracle Server', 'extension' => 'oci8', 'env' => 'OCI']],
    ['firebird', ['firebird'], ['label' => 'Firebird', 'extension' => 'interbase', 'env' => 'FBIRD']],
    ['sqlite', ['sqlite'], ['label' => 'SQLite', 'extension' => 'sqlite3', 'env' => 'SQLITE']],
    ['pdo', ['pdo'], ['pdo' => 'PDO', 'extension' => 'pdo']],
    ['odbc', ['odbc'], ['odbc' => 'ODBC', 'extension' => 'odbc']]
);

// foreach (PDO::getAvailableDrivers() as $driver) {
//     echo $driver . '<br />';
// }

// https://www.connectionstrings.com/sqlite/
// https://www.dbi-services.com/blog/installing-the-odbc-driver-manager-with-sqlite-on-linux/

// https://gist.github.com/amirkdv/9672857
// https://stackoverflow.com/questions/61813881/how-to-access-mdb-data-source-in-php-linux

// https://stackoverflow.com/questions/18556047/php-odbc-connect-to-access-mdb-file-another-server
// https://stackoverflow.com/questions/63707927/how-to-connect-to-microsoft-access-database-from-another-computer-through-odbc-c


// https://gist.github.com/treffynnon/294738
// https://github.com/mpericay/apicollector/blob/master/lib/db/class.odbc.php
// https://snipplr.com/view/24916/php-connection-class-odbc-v1
// https://pt.stackoverflow.com/questions/115042/pdo-dblib-e-codificacao-utf-8
