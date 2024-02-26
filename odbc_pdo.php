<!DOCTYPE html>
<link rel="stylesheet" href="resources/style.css">

<h1>Connecting to Databases</h1>

<?php
try {
    //PDO ODBC ACCDB 
    $dsnPdo = sprintf(
        "odbc:DRIVER=%s;CHARSET=%s;DBQ=%s;UID=%s;PWD=%s;",
        "{Microsoft Access Driver (*.mdb, *.accdb)}",
        "UTF-8",
        "F:\\Projetos\\PHP\\PHP-Generic-Database\\resources\\database\\access\\DB.MDB", // "/var/www/html/resources/DB.MDB"
        "",
        ""
    );

    $odbcPdo = new PDO($dsnPdo);
    $stmt = $odbcPdo->prepare("SELECT id as Codigo, nome as Estado, sigla as UF FROM estado WHERE id > 10");
    $stmt->execute();
    // while ($row = $stmt->fetch()) {
    //     var_dump($row);
    // }
    $result = $stmt->fetchAll();
    var_dump($result);
} catch (Exception $e) {
    var_dump($e);
}

// https://stackoverflow.com/questions/28311687/unable-to-retrieve-utf-8-accented-characters-from-access-via-pdo-odbc