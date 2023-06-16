# PHP-Generic-Database
![PHP Supported Version](https://img.shields.io/badge/php-%3E%3D8.1-blue)

PHP-Generic-Database é um conjunto de classes php para conexão, exibição e manipulação genérica de dados de um banco de dados, possitibilitando centralizar o padronizar todos os mais variados tipos e comportamentos de cada banco de dados em um único formato, utilizando o padrão Strategy.

## Bancos de Dados Suportados
Atualmente o PHP-Generic-Database suporte os seguintes mecanismos/banco de bados:

- mysqli
  - MySQL e MariaDB Nativos
- pgsql
  - PostgreSQL Nativo
- fbird
  - Firebird/Interbase Nativos
- sqlite3
  - SQLite 3.x Nativo
- oci
  - Oracle Nativo
- sqlsvr
  - SQLServer Nativo
- pdo
  - MySQL/MariaDB
  - PostgreSQL
  - Firebird/Interbase
  - SQLite
  - Oracle
  - SQLServer

## Dependências
- PHP >= 81
- Composer

## Configuração
- DLLs/SO Compiladas de cada mecanismo de banco de dados para cada versão do PHP
  - Pacote de DLLs de todos os mecanismos de banco de dados para a versão do PHP 8.1
- Configuração do php.ini

## Instalação Manual
1) Certifique-se que o Composer esteja instalado, caso contrário, instale a partir do [site oficial](https://getcomposer.org/download/).
2) Certifique-se que o Git esteja instalado, caso contrário, instale a partir do [site oficial](https://git-scm.com/downloads).
3) Depois de instalado o Composer e o Git clone este repositório com a linha de comando abaixo:
```
git clone https://github.com/nicksonjean/PHP-Generic-Database.git
```
4) Em seguida execute o seguinte comando para instalar todos pacotes e as dependências deste projeto:
```
composer install
```
5) [Opcional] Caso precise reinstalar execute o seguinte comando:
```
composer setup
```