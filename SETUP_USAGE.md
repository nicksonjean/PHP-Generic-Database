# Guia de Uso: setup.bat individual vs setupAll.bat

## Resumo da Arquitetura

O `docker-compose.yml` possui dois tipos de serviços PHP:

1. **Serviços específicos** (app80, app81, app82, app83, app84, app85):
   - Valores hardcoded no docker-compose.yml
   - Usados pelo `setupAll.bat` para configurar todas as versões de uma vez

2. **Serviço genérico `app`**:
   - Lê variáveis do arquivo `.env` (PHP_VERSION, PHP_PORT, PHP_BASE_TAG)
   - Mantido para compatibilidade com `setup.bat` individual
   - Permite executar apenas uma versão do PHP por vez

## Como usar setup.bat individualmente

### Windows
```batch
.\setup.bat --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d app"
```

### Linux/MacOS
```bash
./setup.sh --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d app"
```

**IMPORTANTE**: Note que o comando usa `app` (serviço genérico), não `app83` (serviço específico).

## Como funciona

1. O `setup.bat` atualiza o arquivo `.env.docker` e copia para `.env`
2. O serviço `app` no docker-compose.yml lê essas variáveis do `.env`
3. O Docker Compose usa essas variáveis para construir e iniciar o container

## Exemplos de uso individual

### PHP 8.0
```batch
.\setup.bat --build-arg PHP_VERSION=8.0 --build-arg PHP_PORT=8000 --run "docker compose up -d app"
```

### PHP 8.1
```batch
.\setup.bat --build-arg PHP_VERSION=8.1 --build-arg PHP_PORT=8100 --run "docker compose up -d app"
```

### PHP 8.2
```batch
.\setup.bat --build-arg PHP_VERSION=8.2 --build-arg PHP_PORT=8200 --run "docker compose up -d app"
```

### PHP 8.3
```batch
.\setup.bat --build-arg PHP_VERSION=8.3 --build-arg PHP_PORT=8300 --run "docker compose up -d app"
```

### PHP 8.4
```batch
.\setup.bat --build-arg PHP_VERSION=8.4 --build-arg PHP_PORT=8400 --run "docker compose up -d app"
```

### PHP 8.5
```batch
.\setup.bat --build-arg PHP_VERSION=8.5 --build-arg PHP_PORT=8500 --run "docker compose up -d app"
```

## Diferenças entre setup.bat e setupAll.bat

| Aspecto | setup.bat (individual) | setupAll.bat |
|---------|------------------------|--------------|
| **Serviço usado** | `app` (genérico) | `app80`, `app81`, etc. (específicos) |
| **Variáveis** | Lê do `.env` | Valores hardcoded no docker-compose.yml |
| **Uso** | Uma versão por vez | Todas as versões de uma vez |
| **Porta** | Definida via argumento | Fixa por serviço (8000, 8100, etc.) |

## Verificação

Após executar o `setup.bat` individual, você pode verificar:

1. **Container em execução**:
   ```batch
   docker ps | findstr php
   ```

2. **Acessar a aplicação**:
   - URL: `http://localhost:PORTA` (ex: http://localhost:8300 para PHP 8.3)

3. **Verificar versão do PHP**:
   ```batch
   docker exec php8.3 php -v
   ```

## Notas Importantes

- O serviço `app` substitui qualquer container anterior com o mesmo nome
- Se você executar `setup.bat` com diferentes versões, o container será recriado
- Para usar múltiplas versões simultaneamente, use `setupAll.bat` ou execute os serviços específicos diretamente:
  ```batch
  docker compose up -d app80 app81 app82
  ```

