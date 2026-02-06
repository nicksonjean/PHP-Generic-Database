# Análise de Viabilidade: Adicionar FrankenPHP como Runner

## Resumo Executivo

**Status**: ⚠️ **VIÁVEL COM LIMITAÇÕES**

A adição do FrankenPHP como terceiro runner é tecnicamente viável, mas apresenta limitações importantes que devem ser consideradas antes da implementação.

---

## 1. Compatibilidade de Versões PHP

### ❌ Limitação Crítica: Versões Não Suportadas

**FrankenPHP suporta apenas:**
- ✅ PHP 8.2
- ✅ PHP 8.3
- ✅ PHP 8.4
- ✅ PHP 8.5

**FrankenPHP NÃO suporta:**
- ❌ PHP 8.0
- ❌ PHP 8.1

**Impacto:**
- O projeto atual suporta PHP 8.0 a 8.5
- Com FrankenPHP, apenas 4 das 6 versões seriam suportadas (66% de cobertura)
- Seria necessário manter Apache e Nginx+PHP-FPM para PHP 8.0 e 8.1

---

## 2. Compatibilidade de Extensões PHP

### Extensões Core (Compatíveis)

Todas as extensões core do PHP são compatíveis:
- ✅ `simplexml`
- ✅ `iconv`
- ✅ `zlib`
- ✅ `pdo`
- ✅ `pdo_sqlite`
- ✅ `mysqli`
- ✅ `pdo_mysql`
- ✅ `pgsql`
- ✅ `pdo_pgsql`
- ✅ `sqlite3` (via ext-sqlite3)

### Extensões PECL (Requerem Verificação ZTS)

**⚠️ Requerem compilação com ZTS (Zend Thread Safety):**

1. **xdebug** ✅
   - Suportado via `install-php-extensions`
   - Compatível com ZTS

2. **yaml** ✅
   - Suportado via `install-php-extensions`
   - Compatível com ZTS

3. **pcov** ✅
   - Suportado via `install-php-extensions`
   - Compatível com ZTS

4. **mcrypt** ⚠️
   - Extensão deprecada desde PHP 7.1
   - Removida no PHP 8.0+
   - **NÃO DISPONÍVEL** em PHP 8.2+
   - **Impacto**: Não pode ser usado no FrankenPHP

### Extensões de Banco de Dados (Requerem Atenção Especial)

#### ✅ Compatíveis (com verificação):
- `pdo_dblib` - Requer compilação ZTS
- `pdo_firebird` - Requer compilação ZTS
- `odbc` - Requer compilação ZTS
- `pdo_odbc` - Requer compilação ZTS

#### ⚠️ Requerem Compilação Manual ZTS:

1. **sqlsrv / pdo_sqlsrv** ⚠️
   - Microsoft SQL Server
   - **Status**: Requer compilação com ZTS
   - **Ação**: Verificar se versões recentes suportam ZTS
   - **Risco**: Médio - pode requerer patches ou compilação manual

2. **oci8 / pdo_oci** ⚠️
   - Oracle Database
   - **Status**: Requer compilação com ZTS
   - **Ação**: Verificar compatibilidade ZTS da versão atual
   - **Risco**: Médio - pode requerer compilação manual

3. **pdo_firebird** ⚠️
   - Firebird Database
   - **Status**: Requer compilação com ZTS
   - **Ação**: Verificar se suporta ZTS
   - **Risco**: Médio

### Extensões Não Suportadas

- ❌ **imap** - Não é thread-safe
- ❌ **newrelic** - Não é thread-safe
- ❌ **mcrypt** - Removida no PHP 8.0+ (não disponível)

### Extensões com Problemas Conhecidos

- ⚠️ **openssl** - Pode crashar sob carga pesada em builds musl libc (não afeta glibc)

---

## 3. Drivers ODBC

### Status dos Drivers ODBC

Os drivers ODBC são bibliotecas externas (não extensões PHP), então a compatibilidade depende da configuração do sistema:

#### ✅ Compatíveis:
- **MySQL/MariaDB ODBC** (`libmaodbc.so` / `libmyodbc9a.so` / `libmyodbc9w.so`)
- **PostgreSQL ODBC** (`odbc-postgresql`)
- **SQL Server ODBC** (`msodbcsql17`)
- **Oracle ODBC** (via Instant Client)
- **Firebird ODBC** (`libOdbcFb.so`)
- **SQLite ODBC** (`libsqliteodbc`)
- **MDBTools** (Access/Excel/Text)

**Nota**: Os drivers ODBC funcionam normalmente, pois são bibliotecas externas. A extensão `pdo_odbc` é que precisa ser compilada com ZTS.

---

## 4. Arquitetura e Diferenças Técnicas

### FrankenPHP vs Apache/Nginx+PHP-FPM

| Aspecto | Apache | Nginx+PHP-FPM | FrankenPHP |
|---------|--------|---------------|------------|
| **Servidor Web** | Apache | Nginx | Caddy (integrado) |
| **Processo PHP** | Módulo Apache | PHP-FPM (FastCGI) | Embed no Caddy |
| **Thread Safety** | Não requer ZTS | Não requer ZTS | **Requer ZTS** |
| **Performance** | Boa | Excelente | Excelente+ |
| **Early Hints** | Não | Não | ✅ Sim (HTTP/103) |
| **Worker Mode** | Não | Não | ✅ Sim (threads) |
| **Hot Reload** | Não | Não | ✅ Sim |

### Vantagens do FrankenPHP

1. **Performance Superior**
   - Worker mode com threads
   - Menos overhead de comunicação
   - Early Hints (HTTP/103)

2. **Funcionalidades Modernas**
   - Hot reload automático
   - Integração nativa com Caddy
   - Suporte a HTTP/2 e HTTP/3

3. **Simplicidade**
   - Um único container (não precisa de Nginx separado)
   - Configuração mais simples

### Desvantagens do FrankenPHP

1. **Limitações de Versão**
   - Não suporta PHP 8.0 e 8.1

2. **Requisito ZTS**
   - Todas as extensões devem ser compiladas com ZTS
   - Algumas extensões podem não estar disponíveis
   - Pode requerer compilação manual de algumas extensões

3. **Ecosystem Menor**
   - Menos documentação e exemplos
   - Menos suporte da comunidade

---

## 5. Estrutura de Implementação Proposta

### Estrutura de Arquivos

```
docker/
├── php/              # Apache (existente)
├── php-fpm/          # PHP-FPM (existente)
└── frankenphp/       # NOVO: FrankenPHP
    └── Dockerfile
```

### Dockerfile Base (Proposta)

```dockerfile
FROM dunglas/frankenphp:latest

# Instalar extensões via install-php-extensions (suporta ZTS)
RUN install-php-extensions \
    pdo_mysql \
    mysqli \
    pdo_pgsql \
    pgsql \
    pdo_sqlite \
    sqlite3 \
    pdo_dblib \
    pdo_firebird \
    odbc \
    pdo_odbc \
    xdebug \
    yaml \
    pcov

# sqlsrv e pdo_sqlsrv - requerem verificação ZTS
RUN install-php-extensions sqlsrv pdo_sqlsrv || \
    (echo "Warning: sqlsrv may require manual ZTS compilation" && exit 0)

# oci8 e pdo_oci - requerem verificação ZTS
RUN install-php-extensions oci8 pdo_oci || \
    (echo "Warning: oci8 may require manual ZTS compilation" && exit 0)

# Instalar dependências ODBC (mesmas do Apache/PHP-FPM)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libsqlite3-dev \
    libsqliteodbc \
    unixodbc-dev \
    libyaml-dev \
    default-libmysqlclient-dev \
    firebird-dev \
    freetds-dev \
    freetds-bin \
    tdsodbc \
    odbc-postgresql \
    odbcinst \
    odbcinst1debian2 \
    libodbc1 \
    unixodbc \
    wget \
    unzip \
    libxml2-dev \
    libxslt-dev \
    libaio1 && \
    apt-get clean -y

# Configurar drivers ODBC (mesmo processo do Apache/PHP-FPM)
# ... (configuração de ODBC drivers)
```

### docker-compose.yml (Adições Propostas)

```yaml
# Template para serviços FrankenPHP
x-frankenphp-service: &frankenphp-service
  <<: *php-base
  build:
    context: .
    dockerfile: ./docker/frankenphp/Dockerfile
    args:
      PHP_VERSION: "8.2"  # Apenas 8.2+
  restart: unless-stopped
  volumes:
    - .:/var/www/html
  networks:
    - internal
  logging:
    driver: "json-file"
    options:
      max-file: "5"
      max-size: "10m"

services:
  # Serviços FrankenPHP (apenas 8.2+)
  php-8.2-frankenphp:
    <<: *frankenphp-service
    build:
      args:
        PHP_VERSION: "8.2"
    image: php-8.2-frankenphp
    container_name: php-8.2-frankenphp
    ports:
      - "8200:80"
      - "8243:443"

  php-8.3-frankenphp:
    <<: *frankenphp-service
    build:
      args:
        PHP_VERSION: "8.3"
    image: php-8.3-frankenphp
    container_name: php-8.3-frankenphp
    ports:
      - "8300:80"
      - "8343:443"

  php-8.4-frankenphp:
    <<: *frankenphp-service
    build:
      args:
        PHP_VERSION: "8.4"
    image: php-8.4-frankenphp
    container_name: php-8.4-frankenphp
    ports:
      - "8400:80"
      - "8443:443"

  php-8.5-frankenphp:
    <<: *frankenphp-service
    build:
      args:
        PHP_VERSION: "8.5"
    image: php-8.5-frankenphp
    container_name: php-8.5-frankenphp
    ports:
      - "8500:80"
      - "8543:443"
```

---

## 6. Impacto e Esforço de Implementação

### Esforço Estimado

| Tarefa | Complexidade | Tempo Estimado |
|--------|--------------|----------------|
| Criar Dockerfile FrankenPHP | Média | 4-6 horas |
| Configurar extensões ZTS | Alta | 8-12 horas |
| Testar compatibilidade extensões | Alta | 6-8 horas |
| Atualizar docker-compose.yml | Baixa | 1-2 horas |
| Atualizar scripts setup.bat/sh | Média | 2-3 horas |
| Documentação | Baixa | 2-3 horas |
| **TOTAL** | **Média-Alta** | **23-34 horas** |

### Riscos Identificados

1. **Alto Risco**: Extensões que podem não funcionar com ZTS
   - `sqlsrv` / `pdo_sqlsrv`
   - `oci8` / `pdo_oci`
   - `pdo_firebird`
   - **Mitigação**: Testar cada extensão individualmente, ter plano B (compilação manual)

2. **Médio Risco**: Compatibilidade de versões
   - PHP 8.0 e 8.1 não suportados
   - **Mitigação**: Manter Apache/Nginx+PHP-FPM para essas versões

3. **Baixo Risco**: Configuração de drivers ODBC
   - Mesma configuração do Apache/PHP-FPM
   - **Mitigação**: Reutilizar scripts existentes

---

## 7. Recomendações

### ✅ Recomendado Implementar Se:

1. **Prioridade de Performance**: Se performance é crítica e você pode viver sem PHP 8.0/8.1
2. **Testes Extensivos**: Se você tem tempo para testar todas as extensões
3. **Suporte a Funcionalidades Modernas**: Se precisa de Early Hints, Worker Mode, etc.
4. **Ambiente de Desenvolvimento**: Para testar e comparar performance

### ⚠️ Não Recomendado Se:

1. **Dependência de PHP 8.0/8.1**: Se precisa suportar essas versões
2. **Extensões Críticas Não Compatíveis**: Se `sqlsrv` ou `oci8` são críticos e não funcionam
3. **Tempo Limitado**: Se não há tempo para testes extensivos
4. **Ambiente de Produção Crítico**: Sem testes completos

### 🎯 Abordagem Recomendada

**Fase 1: Prova de Conceito (POC)**
1. Criar Dockerfile básico para PHP 8.3
2. Testar extensões core (mysqli, pdo_mysql, pgsql, etc.)
3. Testar extensões críticas (sqlsrv, oci8, pdo_firebird)
4. Documentar resultados

**Fase 2: Implementação Parcial**
1. Se POC for bem-sucedida, implementar para PHP 8.2, 8.3, 8.4, 8.5
2. Adicionar ao docker-compose.yml
3. Atualizar scripts setup.bat/sh
4. Documentar limitações

**Fase 3: Produção**
1. Testes extensivos com aplicação real
2. Comparação de performance
3. Decisão final sobre adoção

---

## 8. Checklist de Verificação

Antes de implementar, verificar:

- [ ] Todas as extensões críticas funcionam com ZTS
- [ ] `sqlsrv` / `pdo_sqlsrv` compilam e funcionam
- [ ] `oci8` / `pdo_oci` compilam e funcionam
- [ ] `pdo_firebird` compila e funciona
- [ ] Drivers ODBC configurados corretamente
- [ ] Testes de performance comparativos
- [ ] Documentação atualizada
- [ ] Scripts setup.bat/sh atualizados
- [ ] Aceitação de não suportar PHP 8.0/8.1

---

## 9. Conclusão

### Viabilidade: ⚠️ **VIÁVEL COM LIMITAÇÕES**

**Pontos Positivos:**
- ✅ Maioria das extensões são compatíveis
- ✅ Performance superior
- ✅ Funcionalidades modernas
- ✅ Arquitetura mais simples (um container)

**Pontos de Atenção:**
- ⚠️ Não suporta PHP 8.0 e 8.1
- ⚠️ Requer extensões compiladas com ZTS
- ⚠️ Algumas extensões podem requerer compilação manual
- ⚠️ `mcrypt` não disponível (mas já está deprecado)

**Recomendação Final:**
Implementar como **opção adicional** (não substituição) para PHP 8.2+, mantendo Apache e Nginx+PHP-FPM para todas as versões. Isso permite:
- Comparação de performance
- Testes de compatibilidade
- Escolha do melhor runner por caso de uso
- Suporte completo a todas as versões PHP

---

## 10. Referências

- [FrankenPHP Documentation](https://frankenphp.dev/)
- [FrankenPHP Docker Images](https://github.com/dunglas/frankenphp)
- [ZTS Compatibility Guide](https://www.php.net/manual/en/internals2.threads.php)
- [install-php-extensions Tool](https://github.com/mlocati/docker-php-extension-installer)

---

**Data da Análise**: 2024
**Versão do Documento**: 1.0
