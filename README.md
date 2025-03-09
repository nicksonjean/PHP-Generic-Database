# PHP-Generic-Database

<p align="center">
    <img src="./assets/logo.png" width="256">
</p>

<p align="center">
    <img alt="PHP - &gt;=8.1" src="https://img.shields.io/badge/PHP-%3E=8.1-777BB4?style=for-the-badge&logo=php&logoColor=white">
    <img alt="License" src="https://img.shields.io/github/license/Ileriayo/markdown-badges?style=for-the-badge&color=purple">
</p>

PHP-Generic-Database is a set of PHP classes for connecting, displaying and generically manipulating data from a database, making it possible to centralize or standardize all the most varied types and behaviors of each database in a single format, using the standard Strategy, heavily inspired by [Medoo](https://medoo.in/) and [Dibi](https://dibiphp.com/en/) and [PowerLite](https://www.powerlitepdo.com/).

## Supported Databases

PHP-Generic-Database currently supports the following mechanisms/database:

![MariaDB](https://img.shields.io/badge/MariaDB-4479A1?style=for-the-badge&logo=mariadb&logoColor=white)
![MySQL](https://img.shields.io/badge/mysql-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Postgres](https://img.shields.io/badge/postgres-4479A1.svg?style=for-the-badge&logo=postgresql&logoColor=white)
![MSSQL](https://img.shields.io/badge/MSSQL-4479A1?style=for-the-badge&logo=microsoft%20sql%20server&logoColor=white)
![Oracle](https://img.shields.io/badge/Oracle-4479A1?style=for-the-badge&logo=oracle&logoColor=white)
![Firebird](https://custom-icon-badges.demolab.com/badge/Firebird-4479A1?logo=flatbird&style=for-the-badge&logoColor=white)
![Interbase](https://img.shields.io/badge/Interbase-4479A1?logo=Interbase&style=for-the-badge&logoColor=white)
![SQLite](https://img.shields.io/badge/sqlite-4479A1?style=for-the-badge&logo=sqlite&logoColor=white)

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

- To

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
git clone https://github.com/nicksonjean/PHP-Generic-Database.git
```

4) Then run the following command to install all packages and dependencies for this project:

```bash
composer install
```

5) [Optional] If you need to reinstall, run the following command:

```bash
composer setup
```

## Installation via Docker

1) Make sure Docker Desktop is installed, otherwise install from the [official website](https://www.docker.com/products/docker-desktop/).
2) Create an account to use Docker Desktop/Hub, and be able to clone containers hosted on the Docker network.
3) Once logged in to Docker Hub and with Docker Desktop open on your system, run the command below:

```bash
docker pull php-generic-database:8.3-full
```

or

for Windows:

```bash
.\setup.bat --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d"
```

for Linux or Mac:

```bash
.\setup.sh --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d"
```

4) Docker will download, install and configure a Debian-Like Linux Custom Image as Apache and with PHP 8.1 with all Extensions properly configured.

## Installation via Composer

1) Make sure Composer is installed, otherwise install from the [official website](https://getcomposer.org/download/).
2) After Composer are installed, clone this repository with the command line below:
3) Add PHP-Generic-Database to the composer.json configuration file.

```bash
composer require nicksonjean/php-generic-database
```

4) And update the composer

```bash
composer update
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

## License

PHP-Generic-Database is released under the MIT license.