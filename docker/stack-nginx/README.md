# Stack Nginx + PHP-FPM

Este diretório contém os containers para a stack Nginx + PHP-FPM.

## Estrutura

- `nginx/` - Container Alpine com Nginx (web server)
- `php-fpm/` - Containers PHP-FPM (processador PHP) que trabalham em conjunto com o Nginx

## Como funciona

O Nginx atua como servidor web reverso, processando requisições HTTP/HTTPS e encaminhando requisições PHP para os containers PHP-FPM via FastCGI (porta 9000).

## Uso

Verifique o `docker-compose.yml` para ver como os serviços são configurados e iniciados.
