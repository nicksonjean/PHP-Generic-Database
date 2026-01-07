# Implementação do FrankenPHP - Resumo

## ✅ Arquivos Criados

### 1. Estrutura de Diretórios
```
docker/stack-frankenphp/
├── php-frankenphp/
│   ├── Dockerfile          # Dockerfile principal do FrankenPHP
│   └── Caddyfile           # Configuração do servidor Caddy
└── README.md               # Documentação da stack
```

### 2. Arquivos Modificados

- `docker-compose.yml` - Adicionados serviços FrankenPHP (8.2, 8.3, 8.4, 8.5)
- `setup.bat` - Adicionado suporte para conversão `frankenphp` → `php-{VERSION}-frankenphp`
- `setup.sh` - Adicionado suporte para conversão `frankenphp` → `php-{VERSION}-frankenphp`

## 📋 Serviços Criados no docker-compose.yml

- `php-8.2-frankenphp` - Porta 8200 (HTTP) / 8243 (HTTPS)
- `php-8.3-frankenphp` - Porta 8300 (HTTP) / 8343 (HTTPS)
- `php-8.4-frankenphp` - Porta 8400 (HTTP) / 8443 (HTTPS)
- `php-8.5-frankenphp` - Porta 8500 (HTTP) / 8543 (HTTPS)

## ⚠️ Limitações

- **FrankenPHP suporta apenas PHP 8.2, 8.3, 8.4 e 8.5**
- PHP 8.0 e 8.1 **NÃO são suportados** - use Apache ou Nginx+PHP-FPM

## 🚀 Como Usar

### Uso Individual (Windows)
```batch
.\setup.bat --build-arg PHP_VERSION=8.3 --run "docker compose up -d frankenphp"
```

### Uso Individual (Linux/MacOS)
```bash
./setup.sh --build-arg PHP_VERSION=8.3 --run "docker compose up -d frankenphp"
```

O script automaticamente converte `frankenphp` para `php-8.3-frankenphp`.

### Uso Direto com Docker Compose
```bash
docker compose up -d php-8.3-frankenphp
```

## 🔧 Extensões PHP Instaladas

Todas as extensões são instaladas via `install-php-extensions` que suporta ZTS:

- ✅ Core: `simplexml`, `iconv`, `zlib`, `pdo`, `pdo_sqlite`, `sqlite3`
- ✅ MySQL: `pdo_mysql`, `mysqli`
- ✅ PostgreSQL: `pdo_pgsql`, `pgsql`
- ✅ SQL Server: `sqlsrv`, `pdo_sqlsrv` (com verificação ZTS)
- ✅ Oracle: `oci8`, `pdo_oci` (com verificação ZTS)
- ✅ Firebird: `pdo_firebird`
- ✅ ODBC: `odbc`, `pdo_odbc`
- ✅ DBLIB: `pdo_dblib`
- ✅ Dev: `xdebug`, `yaml`, `pcov`

## 📝 Notas Importantes

1. **ZTS (Zend Thread Safety)**: Todas as extensões devem ser compatíveis com ZTS
2. **Extensões Problemáticas**: Algumas extensões podem requerer compilação manual
3. **Drivers ODBC**: Funcionam normalmente (são bibliotecas externas)
4. **Caddyfile**: Configuração básica incluída, pode ser customizada

## 🔍 Verificação

Após iniciar o container, verifique:

```bash
# Verificar se o container está rodando
docker ps | grep frankenphp

# Verificar logs
docker logs php-8.3-frankenphp

# Testar PHP
docker exec php-8.3-frankenphp php -v

# Testar extensões
docker exec php-8.3-frankenphp php -m

# Acessar aplicação
curl http://localhost:8300
```

## 📚 Referências

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [FrankenPHP GitHub](https://github.com/dunglas/frankenphp)
- [Caddy Web Server](https://caddyserver.com/)

---

**Data da Implementação**: 2024
**Status**: ✅ Completo e Pronto para Uso
