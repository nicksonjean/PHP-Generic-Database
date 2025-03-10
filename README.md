# PHP-Generic-Database

<p align="center">
    <img src="./assets/logo.png" width="256">
</p>

<p align="center">
    <img alt="PHP - &gt;=8.0" src="https://img.shields.io/badge/PHP-%3E=8.0-777BB4?style=for-the-badge&logo=php&logoColor=white">
    <img alt="License" src="https://img.shields.io/github/license/Ileriayo/markdown-badges?style=for-the-badge&color=purple">
</p>

[![Português](https://img.shields.io/badge/Português-Brasil-green.svg?style=for-the-badge&logo=data:image/png;base64,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)](./readme/README-pt-br.md)

PHP-Generic-Database is a set of PHP classes for connecting, displaying and generically manipulating data from a database, making it possible to centralize or standardize all the most varied types and behaviors of each database in a single format, using the standard Strategy, heavily inspired by [Medoo](https://medoo.in/) and [Dibi](https://dibiphp.com/en/) and [PowerLite](https://www.powerlitepdo.com/).

## Supported Databases

PHP-Generic-Database currently supports the following mechanisms/database:

![MariaDB](https://img.shields.io/badge/MariaDB-BA7257?style=for-the-badge&logo=mariadb&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-E48E00?style=for-the-badge&logo=mysql&logoColor=white)
![Postgre](https://img.shields.io/badge/pgsql-31648C.svg?style=for-the-badge&logo=postgresql&logoColor=white)
![SQLSrv](https://img.shields.io/badge/SQLSRV-72818C?style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAMAAACdt4HsAAAAWlBMVEUAAAD///////////////////////////////////////////////////////////////////////////////////////////////////////////////////9ZMre9AAAAHnRSTlMA/u2u5S7z2M/GuN5cUj8KEJZtScAljGShNhd4HIHwZ1f8AAADTklEQVRYw+1XN4IcORAD6H032d79/5sXzK6UaTmj5AIxB2hQhQKBf+tPa5yuv0DrK0T6j9FVUcm0x8/gOdA9GsBmhg/gZyC5Jz0CB5cP8NFedMGQUa6c3id4DFDNPei0BvJ5n+BiBkIcAFRj3fsEg6Vbk/Fnjkof1B9ISDpDksznYPcPZJhY7+mSsyOt4JqO+32GCwAqZ3oZSePkNb3F0lhwO26QMzAuG0nSrqWfIdgi3AJsHIEtxlkceVdUT+85FlJoAAfPw3IdpAcAvSqxdlJIKsZ9KkYyLDiZAOBOnuQ69hAko8vqhCATgFUA+rE0snKnyF13OABUkj4BZk2OYp9GSItTsnbUo2kYJE3QkvNOQzkNABILgI3+52vEa7RmUhewkawvX7j5Kkxt4o9v6YKKY+agpWGcxQYA8Gr41fM/ncFTAnau9CszVlYA9VdrjZnyB7OyDDqRKsEGAJkSGyfgLm33ijSx/aDCI0nVBuy8AUCLmWGTkRROPum4hz+foKlxJR2Qv2qozCTFvGbdVUew1pKbqCdXDGWdSVeT7vfoRFo+yIw2S0HB5z17HoVqUQKaJOemB9Hec5Sgig/DJCkoKA9glu85O7NXj6PftMnFM6b9LXdOrI4kC6CpgWNXjG85oiAj86sPRgDYKc438KSbmsHFE9UCmJSopluFTJoNsBJQAW7HIinPpXvAJJIVKK8HkGJ7qDLQPaEa/SES4N2r7SlUA/oJVq4YOWFiATA2Re73i6DrDSobMJqEGIBlNeSTIu3epsf0qHC9Gs/tzegcKK7EBZhqEKTtwB9s3zoIxTkNCK/6K1Gonrg2u+FeSts96dsCYGECUDzlHTp6oZCCpJhplD8BQCqgBPoCiI7EKVVKuSzIzKPjNWBhOma6CYBmx2D29iufVAAP4yY5079SWuvJixsXALeZX5YSSKrvfefQZYMBGGwcANxbUPSzieEqy4CRG7pkXMdZneNxeVIKcUJfXlBYbzubOVPQeVKEbdz4NcOnmWRvVDssWfPyO2YtLRhRG4/+dBVVPYDMepZLKnLe7u+C7DT05qgC6ZSw4SkjgPJu4taPEyTlps8BAOwH35aztN1HIVS0zpoP/gwvFp0f6QTXf7/P///6D2ZiKukSLWNhAAAAAElFTkSuQmCC&logoColor=white)
![Oracle](https://img.shields.io/badge/Oracle-C84734?style=for-the-badge&logo=data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IS0tIFVwbG9hZGVkIHRvOiBTVkcgUmVwbywgd3d3LnN2Z3JlcG8uY29tLCBHZW5lcmF0b3I6IFNWRyBSZXBvIE1peGVyIFRvb2xzIC0tPgo8c3ZnIHdpZHRoPSI4MDBweCIgaGVpZ2h0PSI4MDBweCIgdmlld0JveD0iMCAwIDI0IDI0IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgogIDxwYXRoIGZpbGw9IiNGRkYiIGZpbGwtcnVsZT0iZXZlbm9kZCIgZD0iTTcuOTU3MzU5LDE4LjkxMjM2NjQgQzQuMTE2NzAyNTIsMTguOTEyMzY2NCAxLDE1LjgwMzQ1OCAxLDExLjk2MTczNzMgQzEsOC4xMjAwMDc3MyA0LjExNjcwMjUyLDUgNy45NTczNTksNSBMMTYuMDQzNzk0OCw1IEMxOS44ODU1MTU2LDUgMjMsOC4xMjAwMDc3MyAyMywxMS45NjE3MzczIEMyMywxNS44MDM0NTggMTkuODg1NTE1NiwxOC45MTIzNjY0IDE2LjA0Mzc5NDgsMTguOTEyMzY2NCBMNy45NTczNTksMTguOTEyMzY2NCBMNy45NTczNTksMTguOTEyMzY2NCBaIE0xNS44NjM5MTc2LDE2LjQ1ODU0ODggQzE4LjM1MjIwMSwxNi40NTg1NDg4IDIwLjM2NzQzOTcsMTQuNDQ4ODU4IDIwLjM2NzQzOTcsMTEuOTYxNzM3MyBDMjAuMzY3NDM5Nyw5LjQ3NDYwNTk1IDE4LjM1MjIwMSw3LjQ1MzgxOTM0IDE1Ljg2MzkxNzYsNy40NTM4MTkzNCBMOC4xMzYwODI0LDcuNDUzODE5MzQgQzUuNjQ4OTUyODUsNy40NTM4MTkzNCAzLjYzMjU1ODU1LDkuNDc0NjA1OTUgMy42MzI1NTg1NSwxMS45NjE3MzczIEMzLjYzMjU1ODU1LDE0LjQ0ODg1OCA1LjY0ODk1Mjg1LDE2LjQ1ODU0ODggOC4xMzYwODI0LDE2LjQ1ODU0ODggTDE1Ljg2MzkxNzYsMTYuNDU4NTQ4OCBMMTUuODYzOTE3NiwxNi40NTg1NDg4IFoiLz4KPC9zdmc+&logoColor=white)
![Firebird](https://custom-icon-badges.demolab.com/badge/Firebird-FF0000?logo=flatbird&style=for-the-badge&logoColor=white)
![Interbase](https://img.shields.io/badge/Interbase-FF0000?style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAANEAAADRCAMAAABl5KfdAAAAgVBMVEUAAAD///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////9d3yJTAAAAKnRSTlMAMpXxm6Hg1Puql/fKss+e7GqK6eLGwKPdu3HYeua3g3VSkGHzWkKlSX+etyczAAAE0UlEQVR42uzZ2XKqQBSF4bUdGASN4BBn45ys93/AUyIGpRFMDBzo4rvxyqJ+pXsXDWq1Wq1Wq9VqtVqtVqvVbsmUZyZ0YTO0gRZmFq++oIMRI4LqO1qMuKi+FqnXX+TyloFsctiuzIFDlnJzlAHvfCCdNNwm71koE+G9PVLItmcxwRvKY8OYxuOc9pAPdFEaM8bJo5wBVeWbyevnfm3xDaZ5R1lsqRAoNiOyIkENKlx1LxiwykGUWM+KrE7QlioDtw4mqxS0ZoIFIoshKxX0wUjSdPWbrFbQjklmCG0d/jCI5KDr7Xb4T4QJJoKLjsVngyLhd+Y7QSGyg/oeLhoWfxEEeN6cgeaosSk6y2Kc5ePCd/jzoChqNuLFcOoXWNVn3AkXsz6f1kYyOS0ZmnQXhWSZjBkJApshXwmKiN/jlWOe8l5aU94bCgLS48tBEfGmTQbCpXXMLavFe+vo9PG1IJW0Dd5YTn3JIWuW/PDwxheCUsja5Z1J90ty3LfnEmbu8wgKyaHb5x3HtGeSx75tLRCQOfMJikhHuUbTbR2ULBG/65qGYbr2+rnmoXrDyYo5BKnkY+wwbjDeiiD0aRu85Yw8ZBnz21IQWDO3IJXYS56pS0uOK4cq512QSjnxESP3IHVU/eX1GOqF5V0WEqSOqj2ft//MLppF71eKD1JHlcoTMRmxM4rGCIjJAoOyR1WsQHijh0cWDvs7BDrMUMhh8HlUqXwAcHhjiYck/FiWIujBqDLVR7gl0tksTdCZLGJRvUVLrUxxbJYr6Oy4ZAYbD61YviAAbWbYINnB4a+0kbcN0zlINGZZgwBhuhNUnlXiIOCT6STpebyca+iq87PN4bgvexAwYRorPoPKHwRhqg9EZFCFIGDENC6+NViNIMhze4OYVQkCBkzjIeCxOkEZy32Ms1WVgrDLvO2kz99roXDCdJM5qxUEOExXuSD0eaVJEIYM6RL0XaRN0LVIn6CwSKOgS5FOQTBIahV0LtIrCIZuQTB0C4KhWxAM3YJg6BYEQ7cgGLoFwdAtCBPdgjDnX+mgHFzdgtDVLQgd3YLg6RYE0S0IcHQLQk+3IHR0C4LoFgT0dQuCzRc0UEKiWxAw1y0Inm5B/9q7u90EgSAMwy9OqhKtISJGG2tNf5O5/wtsTdvYhD2yHjAf89zBm4WFBV2gUQvipBYEj2pBvKsFwVYtCOZqQbypBUGtFgQztSBo1IJgoRYEc7UgWKgFgakFQaMWBK1aEGzUgmCnFgSzoe/Hdn2S0OaOS7kiOr8Y6O6TN1jUdsS2929CG7/ee8+K2ASTar2ku0wKYDKKpOiX2lovqdO71Lbesye0ahB/obypk/c9ENpWbrlUeZ8R2sH7toS2874DkVXjeJYS+w6v8oITka38y6C/5HGTrXhqImvlJodnV7soVS43SDO5QVrLDVKl91qplRuko5esievgJVPiqlzusJt7yQdxTb2kIa5O7rCrB/1f5WtMvGhHWC8ud9i17mqrc3N3sQf7pjZ/g4mdSICJnUjlpCOx9ZM2BGdiJxJgckWY0EL2h2lNDWemcrN6YTpvKX6Z1tRwZoP8Ove/mNRkx5mJvBv7w1R+znVhAk9Wy0mv6DCBZWwp6QklSzetINC4YUgppZRSSimllMbqE+pyG80XO1J+AAAAAElFTkSuQmCC)
![SQLite](https://img.shields.io/badge/sqlite-%2307405e.svg?style=for-the-badge&logo=sqlite&logoColor=white)

## Features

- **Lightweight** - Light, simple and minimalist, easy to use and with a low learning curve.
- **Agnostic** - It can be used in different ways, supporting chainable methods, fluent design, dynamic arguments and static array.
- **Easy** - Easy to learn and use, with a friendly construction.
- **Powerful** - Supports various common and complex SQL queries, data mapping and prevents SQL injection.
- **Compatible** - Supports MySQL/MariaDB, SQLSrv/MSSQL, Interbase/Firebird, PgSQL, OCI, SQLite, and more.
- **Auto Escape** - Automatically escape SQL queries according to the driver dialect or SQL engine used.
- **Friendly** - Works well with every PHP framework, such as Laravel, Codeigniter, CakePHP, and frameworks that support singleton extension or composer.
- **Free** - Under the MIT license, you can use it anywhere, for whatever purpose.

## Requirements

- **PHP >= 8.0**
- **Composer**
- **Native Extensions**
  - **MySQL/MariaDB** ***(MySQLi)*** *[php_mysqli.dll/so]*
  - **PostgreSQL** ***(PgSQL)*** *[php_pgsql.dll/so]*
  - **Oracle** ***(OCI8)*** *[php_oci8_***.dll/so]*
  - **SQL Server** ***(sqlsrv)*** *[php_sqlsrv.dll/so]*
  - **Firebird/Interbase** ***(ibase: gds | firebird: fds)*** *[php_interbase.dll/so]*
  - **SQLite** ***(SQLite3)*** *[php_sqlite3.dll/so]*
- **PDO Extensions**
  - **MySQL/MariaDB** ***(MySQL)*** *[php_pdo_mysql.dll/so]*
  - **PostgreSQL** ***(PgSQL)*** *[php_pdo_pgsql.dll/so]*
  - **Oracle** ***(OCI)*** *[php_pdo_oci.dll/so]*
  - **SQL Server** ***(sqlsrv)*** *[php_pdo_sqlsrv.dll/so]*
  - **Firebird/Interbase** ***(ibase: gds | firebird: fds)*** *[php_pdo_firebird.dll/so]*
  - **SQLite** ***(SQLite)*** *[php_pdo_sqlite.dll/so]*
  - **ODBC** ***(ODBC)*** *[php_pdo_obdc.dll/so]*
- **ODBC Externsions**
  - **MySQL/MariaDB** ***(MySQL)*** *[myodbc8a.dll/so]*
  - **PostgreSQL** ***(PgSQL)*** *[psqlodbc30a.dll/so]*
  - **OCI** ***(ORACLE)*** *[sqora32.dll/so]*
  - **SQL Server** ***(sqlsrv)*** *[sqlsrv32.dll/so]*
  - **Firebird/Interbase** ***(ibase: gds | firebird: fds)*** *[odbcFb.dll/so]*
  - **SQLite** ***(SQLite)*** *[sqlite3odbc.dll/so]*
  - **Access** ***(Access)*** *[aceodbc.dll/so]*
  - **Excel** ***(Excel)*** *[aceodexl.dll/so]*
  - **Text** ***(Text)*** *[aceodtxt.dll/so]*
- **Optional External Formats**
  - **INI** ***(php native compilation)***
  - **XML** ***(ext-libxml, ext-xmlreader, ext-simplexml)***
  - **JSON** ***(php native compilation)***
  - **YAML** ***(ext-yaml)***
  - **NEON** ***[(nette/neon)](https://github.com/nette/neon)***

## Local Instalation with XAMPP

1) Make sure Git is installed, otherwise install from the [official website](https://git-scm.com/downloads).

```bash
git clone https://github.com/nicksonjean/PHP-Generic-Database.git
```

2. Install the [XAMPP](https://www.apachefriends.org/pt_br/index.html).

### Only for Windows

3. Navigate to the `assets/DLL` folder, select the PHP version you installed, and extract the DLL package containing the compiled libraries for each database engine.  
  3.1. DLL package for [PHP 8.0](./assets/DLL/PHP8.0/PHP8.0.zip).  
  3.2. DLL package for [PHP 8.1](./assets/DLL/PHP8.1/PHP8.1.zip).  
  3.3. DLL package for [PHP 8.2](./assets/DLL/PHP8.2/PHP8.2.zip).  
  3.4. DLL package for [PHP 8.3](./assets/DLL/PHP8.3/PHP8.3.zip).  
4. Copy the files from the `DLL` folder to the `PHP/ext` directory.
5. Open the `php.ini` file and uncomment the extensions you want to use, edit the `php.ini` file and remove the &#039;;&#039; for the database extension you want to install as shown in the example below:  

- From

```ini
;extension=php_pdo_mysql.dll
```

- To

```ini
extension=php_pdo_mysql.dll
```

### Only for Linux and MacOS

3. Download the third party libraries like a Oracle and SQLSrv for each database engine and extract them to the `PHP/ext` directory.
4. Compile the PHP source code and install the PHP extension you want to use.
5. Open the `php.ini` file and uncomment the extensions you want to use, edit the `php.ini` file and remove the &#039;;&#039; for the database extension you want to install as shown in the example below:  

- From

```ini
;extension=php_pdo_mysql.so
```

- To

```ini
extension=php_pdo_mysql.so
```

### for All Systems

6. Save it, and restart the PHP or Apache Server.
7. If the extension is installed successfully, you can find it on phpinfo() output.
8) Make sure Composer is installed, otherwise install from the [official website](https://getcomposer.org/download/).
9) After Composer and Git are installed, clone this repository with the command line below:
10) Then run the following command to install all packages and dependencies for this project:

```bash
composer install
```

11) [Optional] If you need to reinstall, run the following command:

```bash
composer setup
```

## Local Installation via Docker

1) Make sure Docker Desktop is installed, otherwise install from the [official website](https://www.docker.com/products/docker-desktop/).
2) Create an account to use Docker Desktop/Hub, and be able to clone containers hosted on the Docker network.
3) Once logged in to Docker Hub and with Docker Desktop open on your system, run the command below:

```bash
docker pull php-generic-database:8.3-full
```

or

### Only for Windows

```bash
.\setup.bat --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d"
```

### Only for Linux and MacOS

```bash
.\setup.sh --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d"
```

4) Docker will download, install and configure a Debian-Like Linux Custom Image as Apache and with PHP 8.x in the choosed port with all Extensions properly configured.

## Documentation

A complete documentation of the lib is available at [Complete Documentation](https://nicksonjean.github.io/PHP-Generic-Database/).

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
    - MySQL/MariaDB with mysqli: [MySQLiConnection.md](./readme/Engines/MySQLiConnection.md)
    - Firebird/Interbase with fbird/ibase: [FirebirdConnection.md](./readme/Engines/FirebirdConnection.md)
    - Oracle with oci8: [OCIConnection.md](./readme/Engines/OCIConnection.md)
    - PostgreSQL with pgsql: [PgSQLConnection.md](./readme/Engines/PgSQLConnection.md)
    - SQL Server with sqlsrv: [SQLSrvConnection.md](./readme/Engines/SQLSrvConnection.md)
    - SQLite with sqlite3: [SQLiteConnection.md](./readme/Engines/SQLiteConnection.md)
    - PDO:
      - [Chainable.md](./readme/Engines/PDOConnection/Chainable.md)
      - [Fluent.md](./readme/Engines/PDOConnection/Fluent.md)
      - [StaticArgs.md](./readme/Engines/PDOConnection/StaticArgs.md)
      - [StaticArray.md](./readme/Engines/PDOConnection/StaticArray.md)
    - ODBC:
      - [Chainable.md](./readme/Engines/ODBCConnection/Chainable.md)
      - [Fluent.md](./readme/Engines/ODBCConnection/Fluent.md)
      - [StaticArgs.md](./readme/Engines/ODBCConnection/StaticArgs.md)
      - [StaticArray.md](./readme/Engines/ODBCConnection/StaticArray.md)
  - Statements: [Statements.md](./readme/Statements.md)
  - Fetches: [Fetches.md](./readme/Fetches.md)
- QueryBuilder:
  - Strategy:
    - [StrategyQueryBuilder.md](./readme/QueryBuilder/StrategyQueryBuilder.md)
  - Engines:
    - MySQL/MariaDB with mysqli: [MySQLiQueryBuilder.md](./readme/Engines/MySQLiQueryBuilder.md)
    - Firebird/Interbase with fbird/ibase: [FirebirdQueryBuilder.md](./readme/Engines/FirebirdQueryBuilder.md)
    - Oracle with oci8: [OCIQueryBuilder.md](./readme/Engines/OCIQueryBuilder.md)
    - PostgreSQL with pgsql: [PgSQLQueryBuilder.md](./readme/Engines/PgSQLQueryBuilder.md)
    - SQL Server with sqlsrv: [SQLSrvQueryBuilder.md](./readme/Engines/SQLSrvQueryBuilder.md)
    - SQLite with sqlite3: [SQLiteQueryBuilder.md](./readme/Engines/SQLiteQueryBuilder.md)
    - PDO: [PDOQueryBuilder.md](./readme/Engines/PDOQueryBuilder.md)
    - ODBC: [ODBCQueryBuilder.md](./readme/Engines/ODBCQueryBuilder.md)

## Diagram

The vertical flowchart/diagram also (from top to bottom) clearly shows the organization of your directory structure, as it allows a more natural visualization of the file and folder hierarchy, for the database abstraction library with support for multiple engines (MySQL, PostgreSQL, SQLite, SQL Server, Firebird, OCI and ODBC), as well as a well-defined structure of abstract classes, interfaces and helpers.

```mermaid
flowchart TB
    Root["PHP Generic Database"]
    
    Root --- Connection["Connection.php"]
    Root --- QueryBuilder["QueryBuilder.php"]
    Root --- Abstract["Abstract/"]
    Root --- Core["Core/"]
    Root --- Engine["Engine/"]
    Root --- Generic["Generic/"]
    Root --- Helpers["Helpers/"]
    Root --- Interfaces["Interfaces/"]
    Root --- Modules["Modules/"]
    Root --- Shared["Shared/"]
    
    Abstract --- AbstractFiles["
        AbstractArguments.php
        AbstractAttributes.php
        AbstractFetch.php
        AbstractOptions.php
        AbstractStatements.php
    "]
    
    Core --- CoreFiles["
        Build.php
        Column.php
        Condition.php
        Entity.php
        Grouping.php
        Having.php
        Insert.php
        Join.php
        Junction.php
        Limit.php
        Query.php
        Select.php
        Sorting.php
        Table.php
        Types.php
        Where.php
    "]
    Core --- Emulated["Emulated/"]
    Core --- Native["Native/"]
    
    Emulated --- EmulatedFiles["
        Build.php
        Column.php
        Condition.php
        Entity.php
        Grouping.php
        Having.php
        Insert.php
        Join.php
        Junction.php
        Limit.php
        Query.php
        Select.php
        Sorting.php
        Table.php
        Types.php
        Where.php
    "]
    
    Native --- NativeFiles["
        Build.php
        Column.php
        Condition.php
        Entity.php
        Grouping.php
        Having.php
        Insert.php
        Join.php
        Junction.php
        Limit.php
        Query.php
        Select.php
        Sorting.php
        Table.php
        Types.php
        Where.php
    "]
    
    Engine --- EngineFiles["
        FirebirdConnection.php
        FirebirdQueryBuilder.php
        MySQLiConnection.php
        MySQLiQueryBuilder.php
        OCIConnection.php
        OCIQueryBuilder.php
        ODBCConnection.php
        ODBCQueryBuilder.php
        PDOConnection.php
        PDOQueryBuilder.php
        PgSQLConnection.php
        PgSQLQueryBuilder.php
        SQLiteConnection.php
        SQLiteQueryBuilder.php
        SQLSrvConnection.php
        SQLSrvQueryBuilder.php
    "]
    
    Engine --- FirebirdDir["Firebird/"]
    Engine --- MySQLiDir["MySQLi/"]
    Engine --- OCIDir["OCI/"]
    Engine --- ODBCDir["ODBC/"]
    Engine --- PDODir["PDO/"]
    Engine --- PgSQLDir["PgSQL/"]
    Engine --- SQLiteDir["SQLite/"]
    Engine --- SQLSrvDir["SQLSrv/"]
    
    FirebirdDir --- FirebirdSubdir["
        Connection/
        QueryBuilder/
    "]
    
    FirebirdSubdir --- FirebirdConnection["Connection:
        Firebird.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    FirebirdSubdir --- FirebirdBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    MySQLiDir --- MySQLiSubdir["
        Connection/
        QueryBuilder/
    "]
    
    MySQLiSubdir --- MySQLiConnection["Connection:
        MySQL.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    MySQLiSubdir --- MySQLiBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    OCIDir --- OCISubdir["
        Connection/
        QueryBuilder/
    "]

    OCISubdir --- OCIConnection["Connection:
        OCI.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    OCISubdir --- OCIBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    ODBCDir --- ODBCSubdir["
        Connection/
        QueryBuilder/
    "]

    ODBCSubdir --- ODBCConnection["Connection:
        ODBC.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    ODBCSubdir --- ODBCBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    PDODir --- PDOSubdir["
        Connection/
        QueryBuilder/
    "]
    
    PDOSubdir --- PDOConnection["Connection:
        XPDO.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    PDOSubdir --- PDOBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    PgSQLDir --- PgSQLSubdir["
        Connection/
        QueryBuilder/
    "]

    PgSQLSubdir --- PgSQLConnection["Connection:
        PgSQL.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    PgSQLSubdir --- PgSQLBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    SQLiteDir --- SQLiteSubdir["
        Connection/
        QueryBuilder/
    "]

    SQLiteSubdir --- SQLiteConnection["Connection:
        SQLite.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    SQLiteSubdir --- SQLiteBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    SQLSrvDir --- SQLSrvSubdir["
        Connection/
        QueryBuilder/
    "]
    
    SQLSrvSubdir --- SQLSrvConnection["Connection:
        SQLSrv.php
        Arguments/
        Attributes/
        DSN/
        Fetch/
        Options/
        Report/
        Statements/
        Transactions/
    "]
    
    SQLSrvSubdir --- SQLSrvBuilder["QueryBuilder:
        Builder.php
        Context.php
        Criteria.php
        Internal.php
        Query.php
        Regex.php
    "]
    
    Generic --- GenericDir["
        Connection/
        Fetch/
        Statements/
    "]
    
    GenericDir --- GenericConnection["Connection:
        Methods.php
        Settings.php
    "]
    
    GenericDir --- GenericFetch["Fetch:
        FetchCache.php
    "]
    
    GenericDir --- GenericStatements["Statements:
        Metadata.php
        QueryMetadata.php
        RowsMetadata.php
    "]
    
    Helpers --- HelpersFiles["
        Compare.php
        Errors.php
        Exceptions.php
        Generators.php
        Hash.php
        Path.php
        Reflections.php
        Schemas.php
        Validations.php
    "]
    
    Helpers --- ParsersDir["Parsers/"]
    Helpers --- TypesDir["Types/"]
    
    ParsersDir --- ParsersFiles["
        INI.php
        JSON.php
        NEON.php
        SQL.php
        TXT.php
        XML.php
        YAML.php
        SQL/
    "]
    
    TypesDir --- TypesSubdirs["
        Compounds/
        Scalars/
        Specials/
    "]
    
    TypesSubdirs --- CompoundsDir["Compounds:
        Arrays.php
    "]
    
    TypesSubdirs --- ScalarsDir["Scalars:
        Strings.php
    "]
    
    TypesSubdirs --- SpecialsDir["Specials:
        Datetimes.php
        Resources.php
        Datetimes/
    "]
    
    Interfaces --- InterfacesFiles["
        IConnection.php
        IQueryBuilder.php
    "]
    
    Interfaces --- ConnectionInterfaces["Connection/"]
    Interfaces --- QueryBuilderInterfaces["QueryBuilder/"]
    Interfaces --- StrategyInterfaces["Strategy/"]
    
    ConnectionInterfaces --- ConnInterfaceFiles["
        IArguments.php
        IArgumentsAbstract.php
        IArgumentsStrategy.php
        IAttributes.php
        IAttributesAbstract.php
        IConstants.php
        IDSN.php
        IFetch.php
        IFetchAbstract.php
        IFetchStrategy.php
        IOptions.php
        IOptionsAbstract.php
        IReport.php
        IStatements.php
        IStatementsAbstract.php
        ITransactions.php
    "]
    
    StrategyInterfaces --- StrategyInterfaceFiles["
        IConnectionStrategy.php
        IQueryBuilderStrategy.php
    "]
    
    Modules --- ModulesFiles["
        Chainable.php
        Fluent.php
        StaticArgs.php
        StaticArray.php
    "]
    
    Shared --- SharedFiles["
        Caller.php
        Cleaner.php
        Enumerator.php
        Getter.php
        Objectable.php
        Property.php
        Registry.php
        Run.php
        Setter.php
        Singleton.php
        Transporter.php
    "]
```

## Usage Examples

To get you started quickly, here are some basic examples of how to use the library:

### Example 1: Performing a FetchAll on a Simple MySQL Database Query

```php
use GenericDatabase\Connection;

$context = Connection::setEngine('mysqli')
                ::setHost('localhost')
                ::setPort(3306)
                ::setDatabase('demodev')
                ::setUser('root')
                ::setPassword('masterkey')
                ::setCharset('utf8')
                ->connect();

$results = $context->query('SELECT id AS Codigo, nome AS Estado, sigla AS Sigla FROM estado WHERE id >= 25');

var_dump($results->fetchAll());
```

### Example 2: Using QueryBuilder

```php
use GenericDatabase\QueryBuilder;

$context = Connection::setEngine('mysqli')
                ::setHost('localhost')
                ::setPort(3306)
                ::setDatabase('demodev')
                ::setUser('root')
                ::setPassword('masterkey')
                ::setCharset('utf8')
                ->connect();

$results = (new QueryBuilder($context))::select(['e.id AS Codigo', 'e.nome AS Estado', 'e.sigla AS Sigla'])
    ->from(['estado e'])
    ->where(['e.id >= 25']);

var_dump($results->fetchAll());
```

### Example 3: Transactions with PDO

```php
use GenericDatabase\Connection;

$context = Connection::setEngine('pdo')
                ::setHost('localhost')
                ::setPort(3306)
                ::setDatabase('demodev')
                ::setUser('root')
                ::setPassword('masterkey')
                ::setCharset('utf8')
                ->connect();

try {
    $context->beginTransaction();

    $b = $context->prepare('INSERT INTO estado (nome, sigla) VALUES (:nome, :sigla)', [[':nome' => 'TESTE', ':sigla' => 'T1'], [':nome' => 'TESTE', ':sigla' => 'T2'], [':nome' => 'TESTE', ':sigla' => 'T5']]);
    var_dump($b->getAllMetadata());

    var_dump($b->lastInsertId('estado'));

    $c = $context->prepare('UPDATE estado SET sigla = :sigla WHERE nome = :nome', [':sigla' => 'T3', ':nome' => 'TESTE']);
    var_dump($c->getAllMetadata());

    $d = $context->query("UPDATE estado SET sigla = 'T4' WHERE nome = 'TESTE'");
    var_dump($d->getAllMetadata());

    $f = $context->query("DELETE FROM estado WHERE nome IN ('TESTE')");
    var_dump($f->getAllMetadata());

    $context->commit();

    var_dump("Transação completada com sucesso!");
} catch (Exception $e) {

    $context->rollback();
    var_dump("Erro na transação: " . $e->getMessage());
}
```

## Contributing

Contributions are welcome! If you want to contribute to the project, follow these steps:

1. Fork the repository
2. Create a branch for your feature (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -m 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Open a Pull Request

## License

PHP-Generic-Database is released under the MIT license.