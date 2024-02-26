# PHP-Generic-Database

<p align="center">
    <img src="./assets/logo.png" width="256">
</p>

<p align="center">
    <img alt="PHP - &gt;=8.1" src="https://img.shields.io/badge/PHP-%3E=8.1-777BB4?style=for-the-badge&logo=php&logoColor=white">
    <img alt="License" src="https://img.shields.io/github/license/Ileriayo/markdown-badges?style=for-the-badge&color=purple">
</p>

PHP-Generic-Database is a set of PHP classes for connecting, displaying and generically manipulating data from a database, making it possible to centralize or standardize all the most varied types and behaviors of each database in a single format, using the standard Strategy, heavily inspired by [Medoo](https://medoo.in/) and [Dibi](https://dibiphp.com/en/).

## Supported Databases

PHP-Generic-Database currently supports the following mechanisms/database:

![MariaDB](https://img.shields.io/badge/MariaDB-BA7257?style=for-the-badge&logo=mariadb&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-E48E00.svg?style=for-the-badge&logo=mysql&logoColor=white)
![PgSQL](https://img.shields.io/badge/postgres-31648C.svg?style=for-the-badge&logo=postgresql&logoColor=white)
![MicrosoftSQLServer](https://img.shields.io/badge/SQLSERVER-72818C?style=for-the-badge&logo=microsoft%20sql%20server&logoColor=white)
![OCI](https://img.shields.io/badge/OCI-C84734?style=for-the-badge&logo=oracle&logoColor=white)
![Firebird](https://img.shields.io/badge/Fbird-F5B60F?style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAMAAACdt4HsAAAArlBMVEUAAAD///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////8tivQqAAAAOXRSTlMABdgSNvzr30vz5MKyDA/4vS3vrpoJysadgHVVTyLoeWplRjAoFqSQb1o7qKGWioZBHdHPYKV9t4Mw5y1WAAAC70lEQVRYw+2X2XqiQBCF2ZFFUUARV9xinGiM0Szn/V9sBDLprg7QJt83d/lvEk6niuqq6qKj/PJf6SwvE/36I4uPTz+wHh/Tca+T/9o2Rn4ST9rfMTdetCF561MEc7i62bwbDKeieNoCvnGLuT6Et65aOMVArEvtWxYWdWt/NLgtiX0Ku1e/2o+B90b7PbyO0sQ9sG9YjvCmSkK8AA+1i3fwCntZDHc1S++wO4qcIXCszj/MFcnYurpmqg1MqupvYcQ/T204/rhd+SZYelUBnsW9BoA7bJXb4j0lVWkwgCUJNAOelgkAq3scG62YWxsD+CM66CJhD5uD5oaAq7nIPbhu6Lzy2bGA7tcAxp8PKxccYeu6AZqLBwCG2AJW/zOdM1DuTgplATELHQcvXJZFrJ1wqgA4pGcmwIVlII9g5nVDMEbEQQ8QeuEAcE20TjBvXx09gkGO8SpXDnzNBghJa3z8OedhQFJeKConzGBXDiHmwFU5fVkkhqvDPCBtyHTmwOYd7HIlmDPBB84V9iPUbCEtJJ8JGqCJ1p3LAByPdPAAxMQBZn1iPt0K3UQmpV1IDhNMwCRfglcHlDfaBgUmHwE5ixsPIr2vKYBDcoCUjRYNIkdhJIk5iEwg4U62iCccpZKIq1cAmCc2LgTcDSmPBYh9sMpT/u/5DAGTTh8fJRYvJ6zX1hDpilO95Mz35jZXdmQH1T2kR5/ykJw+ltVFUwQ7m8lkyLTDXLrPf83AINNE7c35+gYbhSfOtXDDIiAM0tHWZy8nR4l0Z1TuRg7rXKE4C1YFCWx+kBCCXv7VkFP1eT0Uuq0rHiSwyhDa7sfKK+QE06o7GAr0NeRkNZeXcnMaJNTekrbl4j0k0JuU2E6BLqtD0nDvfiw+WYvm9xN7kcwEDMVHPXtVclV/ht3uD1CDkyky1NQ8K/2aGKKpcgPTB19XRk7F7lvKjRjLq5u9SazDl2//57ROB2HuxHRcbzvRlR+g7iJN8+JspfzSwF/pPxQTLGx+KwAAAABJRU5ErkJggg==)
![Interbase](https://img.shields.io/badge/Ibase-ED1F35?style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAANEAAADRCAMAAABl5KfdAAAAgVBMVEUAAAD///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////9d3yJTAAAAKnRSTlMAMpXxm6Hg1Puql/fKss+e7GqK6eLGwKPdu3HYeua3g3VSkGHzWkKlSX+etyczAAAE0UlEQVR42uzZ2XKqQBSF4bUdGASN4BBn45ys93/AUyIGpRFMDBzo4rvxyqJ+pXsXDWq1Wq1Wq9VqtVqtVqvVbsmUZyZ0YTO0gRZmFq++oIMRI4LqO1qMuKi+FqnXX+TyloFsctiuzIFDlnJzlAHvfCCdNNwm71koE+G9PVLItmcxwRvKY8OYxuOc9pAPdFEaM8bJo5wBVeWbyevnfm3xDaZ5R1lsqRAoNiOyIkENKlx1LxiwykGUWM+KrE7QlioDtw4mqxS0ZoIFIoshKxX0wUjSdPWbrFbQjklmCG0d/jCI5KDr7Xb4T4QJJoKLjsVngyLhd+Y7QSGyg/oeLhoWfxEEeN6cgeaosSk6y2Kc5ePCd/jzoChqNuLFcOoXWNVn3AkXsz6f1kYyOS0ZmnQXhWSZjBkJApshXwmKiN/jlWOe8l5aU94bCgLS48tBEfGmTQbCpXXMLavFe+vo9PG1IJW0Dd5YTn3JIWuW/PDwxheCUsja5Z1J90ty3LfnEmbu8wgKyaHb5x3HtGeSx75tLRCQOfMJikhHuUbTbR2ULBG/65qGYbr2+rnmoXrDyYo5BKnkY+wwbjDeiiD0aRu85Yw8ZBnz21IQWDO3IJXYS56pS0uOK4cq512QSjnxESP3IHVU/eX1GOqF5V0WEqSOqj2ft//MLppF71eKD1JHlcoTMRmxM4rGCIjJAoOyR1WsQHijh0cWDvs7BDrMUMhh8HlUqXwAcHhjiYck/FiWIujBqDLVR7gl0tksTdCZLGJRvUVLrUxxbJYr6Oy4ZAYbD61YviAAbWbYINnB4a+0kbcN0zlINGZZgwBhuhNUnlXiIOCT6STpebyca+iq87PN4bgvexAwYRorPoPKHwRhqg9EZFCFIGDENC6+NViNIMhze4OYVQkCBkzjIeCxOkEZy32Ms1WVgrDLvO2kz99roXDCdJM5qxUEOExXuSD0eaVJEIYM6RL0XaRN0LVIn6CwSKOgS5FOQTBIahV0LtIrCIZuQTB0C4KhWxAM3YJg6BYEQ7cgGLoFwdAtCBPdgjDnX+mgHFzdgtDVLQgd3YLg6RYE0S0IcHQLQk+3IHR0C4LoFgT0dQuCzRc0UEKiWxAw1y0Inm5B/9q7u90EgSAMwy9OqhKtISJGG2tNf5O5/wtsTdvYhD2yHjAf89zBm4WFBV2gUQvipBYEj2pBvKsFwVYtCOZqQbypBUGtFgQztSBo1IJgoRYEc7UgWKgFgakFQaMWBK1aEGzUgmCnFgSzoe/Hdn2S0OaOS7kiOr8Y6O6TN1jUdsS2929CG7/ee8+K2ASTar2ku0wKYDKKpOiX2lovqdO71Lbesye0ahB/obypk/c9ENpWbrlUeZ8R2sH7toS2874DkVXjeJYS+w6v8oITka38y6C/5HGTrXhqImvlJodnV7soVS43SDO5QVrLDVKl91qplRuko5esievgJVPiqlzusJt7yQdxTb2kIa5O7rCrB/1f5WtMvGhHWC8ud9i17mqrc3N3sQf7pjZ/g4mdSICJnUjlpCOx9ZM2BGdiJxJgckWY0EL2h2lNDWemcrN6YTpvKX6Z1tRwZoP8Ove/mNRkx5mJvBv7w1R+znVhAk9Wy0mv6DCBZWwp6QklSzetINC4YUgppZRSSimllMbqE+pyG80XO1J+AAAAAElFTkSuQmCC)
![SQLite](https://img.shields.io/badge/sqlite-%2307405e.svg?style=for-the-badge&logo=sqlite&logoColor=white)

## Features

- **Lightweight** - Light, simple and minimalist, easy to use and with a low learning curve.
- **Agnostic** - It can be used in different ways, supporting chainable methods, fluent design, dynamic arguments and static array.
- **Easy** - Easy to learn and use, with a friendly construction.
- **Powerful** - Supports various common and complex SQL queries, data mapping and prevents SQL injection.
- **Compatible** - Supports MySQL/MariaDB, SQLSrv/MSSQL, Interbase/Firebird, PgSQL, OCI, SQLite, and more.
- **Auto Escape** - Automatically escape SQL queries according to the driver dialect or SQL engine used.
- **Friendly** - Works well with every PHP framework, such as Laravel, Codeigniter, Yii, Slim, and frameworks that support singleton extension or composer.
- **Free** - Under the MIT license, you can use it anywhere, for whatever purpose.

## Requirements

- **PHP >= 8.1**
- **Composer**
- **Native Extensions**
  - **MySQL/MariaDB** ***(MySQLi)*** *[php_mysqli.dll/so]*
  - **PgSQL** ***(PgSQL)*** *[php_pgsql.dll/so]*
  - **OCI** ***(ORACLE8)*** *[php_oci8_19.dll/so]*
  - **SQLSrv/MSSQL** ***(sqlsrv, mssql, dblib)*** *[php_sqlsrv.dll/so]*
  - **Firebird/Interbase** ***(ibase: gds/firebird: fds)*** *[php_interbase.dll/so]*
  - **SQLite** ***(SQLite3)*** *[php_sqlite3.dll/so]*
- **PDO Extensions**
  - **MySQL/MariaDB** ***(MySQL)*** *[php_pdo_mysql.dll/so]*
  - **PgSQL** ***(PgSQL)*** *[php_pdo_pgsql.dll/so]*
  - **OCI** ***(ORACLE)*** *[php_pdo_oci.dll/so]*
  - **SQLSrv/MSSQL** ***(sqlsrv, mssql, dblib)*** *[php_pdo_sqlsrv.dll/so]*
  - **Firebird/Interbase** ***(ibase: gds/firebird: fds)*** *[php_pdo_firebird.dll/so]*
  - **SQLite** ***(SQLite)*** *[php_pdo_sqlite.dll/so]*
  - **ODBC** ***(ODBC/MDB)*** *[php_pdo_obdc.dll/so]*
- **ODBC Externsions**
  - **ODBC** ***(ODBC/MDB)*** *[php_obdc.dll/so]*
- **Optional External Formats**
  - **INI** ***(php native compilation)***
  - **XML** ***(ext-libxml, ext-xmlreader, ext-simplexml)***
  - **JSON** ***(php native compilation)***
  - **YAML** ***(ext-yaml)***

## PHP Settings

- DLLs Compiled from each database engine for each PHP version.
  - DLL package for [PHP 8.1](./assets/DLL/PHP8.1/PHP8.1.zip) version.
  - DLL package for [PHP 8.2](./assets/DLL/PHP8.2/PHP8.2.zip) version.
- PHP.ini configuration and extension instalation.

## Extension Instalation

  1) Edit the php.ini file and remove the &#039;;&#039; for the database extension you want to install.
  2) The .dll is for Windows and the .so is for Linux/UNIX.
  3) Uncomment the lines of the extensions you want to enable.

- From

```ini
;extension=php_pdo_mysql.dll
;extension=php_pdo_mysql.so
```

To

```ini
extension=php_pdo_mysql.dll
extension=php_pdo_mysql.so
```

4) Save it, and restart the PHP or Apache Server.
5) If the extension is installed successfully, you can find it on phpinfo() output.

## Manual Installation

1) Make sure Composer is installed, otherwise install from the [official website](https://getcomposer.org/download/).
2) Make sure Git is installed, otherwise install from the [official website](https://git-scm.com/downloads).
3) After Composer and Git are installed, clone this repository with the command line below:

```bash
$ git clone https://github.com/nicksonjean/PHP-Generic-Database.git
```

4) Then run the following command to install all packages and dependencies for this project:

```bash
$ composer install
```

5) [Optional] If you need to reinstall, run the following command:

```bash
$ composer setup
```

## Installation via Docker

1) Make sure Docker Desktop is installed, otherwise install from the [official website](https://www.docker.com/products/docker-desktop/).
2) Create an account to use Docker Desktop/Hub, and be able to clone containers hosted on the Docker network.
3) Once logged in to Docker Hub and with Docker Desktop open on your system, run the command below:

```bash
$ docker pull php-generic-database:8.1-full
```

4) Docker will download, install and configure a Debian-Like Linux Custom Image as Apache and with PHP 8.1 with all Extensions properly configured.

## Installation via Composer

1) Make sure Composer is installed, otherwise install from the [official website](https://getcomposer.org/download/).
2) After Composer are installed, clone this repository with the command line below:
3) Add PHP-Generic-Database to the composer.json configuration file.

```bash
$ composer require nicksonjean/php-generic-database
```

4) And update the composer

```bash
$ composer update
```

## Documentation

### How to use

Below is a series of readmes containing examples of how to use the lib and a [topology](./assets/topology.png) image of the native drivers and pdo.

- Connection:
  - Strategy:
    - [Chainable.md](./readme/Connection/Strategy/Chainable.md)
    - [Fluent.md](./readme/Connection/Strategy/Fluent.md)
    - [StaticArgs.md](./readme/Connection/Strategy/StaticArgs.md)
    - [StaticArray.md](./readme/Connection/Strategy/StaticArray.md)
  - Modules:
    - [Chainable.md](./readme/Connection/Modules/Chainable.md)
    - [Fluent.md](./readme/Connection/Modules/Fluent.md)
    - [StaticArgs.md](./readme/Connection/Modules/StaticArgs.md)
    - [StaticArray.md](./readme/Connection/Modules/StaticArray.md)
- Engines:
  - MySQLi: [MySQLiEngine.md](./readme/Engines/MySQLiEngine.md)
  - Firebird: [FirebirdEngine.md](./readme/Engines/FirebirdEngine.md)
  - ORACLE8: [OCIEngine.md](./readme/Engines/OCIEngine.md)
  - PgSQL: [PgSQLEngine.md](./readme/Engines/PgSQLEngine.md)
  - SQLSrv: [SQLSrvEngine.md](./readme/Engines/SQLSrvEngine.md)
  - SQLite3: [SQLiteEngine.md](./readme/Engines/SQLiteEngine.md)
  - PDO:
    - [Chainable.md](./readme/Engines/PDOEngine/Chainable.md)
    - [Fluent.md](./readme/Engines/PDOEngine/Fluent.md)
    - [StaticArgs.md](./readme/Engines/PDOEngine/StaticArgs.md)
    - [StaticArray.md](./readme/Engines/PDOEngine/StaticArray.md)
- Statements: [Statements.md](./readme/Statements.md)
- Fetches: [Fetches.md](./readme/Fetches.md)

## License

PHP-Generic-Database is released under the MIT license.

## ToDo

- Infrastructure
  - [x] Creation of the Container in Docker.
  - [ ] Creation of Migrations between all Databases.
- Source
  - Connection
    - [x] Possibility of use with Fluent Design and Chained Methods.
    - [x] Improved use of the Static Arguments format, now using array by key and value for arguments.
    - [x] Improved use of the Static Arguments format, now using named arguments.
  - [x] Implement fetch and fetchAll methods.
  - [ ] Add transaction, commit and rollback support.
  - [ ] Added compatibility for ODBC engine.
  - [ ] Added compatibility for MongoDB Database.
  - [ ] Added compatibility for Cassandra Database.
  - [ ] Added compatibility for SyBase Database.
  - [ ] Added compatibility for dBase Database.
  - [ ] Query builder creation.
