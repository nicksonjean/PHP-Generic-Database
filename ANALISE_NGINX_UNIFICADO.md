# Análise: Unificação de Servidores Nginx

## Situação Atual

Atualmente, existem **6 serviços Nginx separados** no `docker-compose.yml`:
- `nginx80` → porta 8000:80 (PHP 8.0)
- `nginx81` → porta 8100:80 (PHP 8.1)
- `nginx82` → porta 8200:80 (PHP 8.2)
- `nginx83` → porta 8300:80 (PHP 8.3)
- `nginx84` → porta 8400:80 (PHP 8.4)
- `nginx85` → porta 8500:80 (PHP 8.5)

Cada serviço:
- Expõe portas HTTP (8000-8500) e HTTPS (8043-8543)
- Possui variável de ambiente `PHP_SERVICE` (app80, app81, etc.)
- Usa `docker-entrypoint.sh` para substituir `php:9000` pelo serviço PHP-FPM correspondente
- Tem configurações separadas mas idênticas

## Viabilidade da Solução Proposta

### ✅ **É TOTALMENTE POSSÍVEL**

O Nginx suporta nativamente múltiplos blocos `server` escutando em portas diferentes, cada um podendo ter seu próprio upstream para PHP-FPM.

### Arquitetura da Solução

**Servidor Nginx Único:**
- Escuta em múltiplas portas (8000, 8100, 8200, 8300, 8400, 8500)
- Cada porta roteia para o serviço PHP-FPM correspondente (app80, app81, etc.)
- Mapeamento de portas permanece igual no `docker-compose.yml`

### Exemplo de Configuração

```nginx
# Bloco para PHP 8.0 (porta 8000)
server {
    listen 8000;
    server_name _;
    # ... configurações ...
    location ~ \.php$ {
        fastcgi_pass app80:9000;
        # ... outros parâmetros ...
    }
}

# Bloco para PHP 8.1 (porta 8100)
server {
    listen 8100;
    server_name _;
    # ... configurações ...
    location ~ \.php$ {
        fastcgi_pass app81:9000;
        # ... outros parâmetros ...
    }
}

# Repetir para PHP 8.2, 8.3, 8.4, 8.5...
```

## Esforço Estimado

### **Complexidade: MÉDIA a ALTA**

### Tarefas Necessárias:

1. **Modificar `docker/nginx/my-site.conf` e `docker/nginx/default-ssl.conf`**
   - Criar template com múltiplos blocos `server`
   - Cada bloco escuta em porta diferente
   - Cada bloco aponta para upstream PHP-FPM diferente
   - **Tempo:** 2-3 horas

2. **Atualizar `docker/nginx/docker-entrypoint.sh`**
   - Remover substituição via `sed` (não será mais necessária)
   - Ou adaptar para gerar múltiplos blocos dinamicamente via template
   - **Tempo:** 1-2 horas

3. **Refatorar `docker-compose.yml`**
   - Remover serviços `nginx80` até `nginx85`
   - Criar serviço `nginx` único
   - Mapear todas as portas (8000-8500 e 8043-8543) no mesmo serviço
   - Remover variável de ambiente `PHP_SERVICE`
   - **Tempo:** 1 hora

4. **Testes e Validação**
   - Testar cada porta individualmente
   - Verificar se cada porta roteia corretamente para a versão PHP correspondente
   - Testar SSL em todas as portas
   - **Tempo:** 2-3 horas

### **Tempo Total Estimado: 6-9 horas**

## Vantagens da Solução

✅ **Redução de Recursos:**
- Menos containers (6 nginx → 1 nginx)
- Menor consumo de memória
- Menos processos em execução

✅ **Manutenção Simplificada:**
- Uma única configuração para manter
- Atualizações aplicadas uma vez só
- Logs centralizados (ou separados por porta)

✅ **Orquestração Mais Simples:**
- Menos serviços no docker-compose.yml
- Menos dependências para gerenciar

## Desvantagens e Considerações

⚠️ **Pontos de Atenção:**

1. **Logs:** Pode ser necessário separar logs por porta para facilitar debugging
2. **Configuração Mais Complexa:** O arquivo de configuração ficará maior (mas mais organizado)
3. **Restart Único:** Reiniciar o nginx afeta todas as versões PHP simultaneamente
4. **SSL:** Cada porta precisa do mesmo certificado (ou certificados diferentes se necessário)

## Recomendações

### Abordagem Recomendada:

**Opção 1: Template Estático (Mais Simples)**
- Criar arquivo de configuração com todos os blocos `server` hardcoded
- Mais simples de implementar e manter
- Menos flexível para adicionar novas versões

**Opção 2: Template Dinâmico (Mais Flexível)**
- Gerar configuração dinamicamente no `docker-entrypoint.sh`
- Usar variáveis de ambiente para definir versões PHP
- Mais complexo, mas permite adicionar novas versões facilmente

### Implementação Sugerida:

1. Criar template base com um bloco `server` por versão PHP
2. Manter a mesma estrutura de configuração atual
3. Remover lógica de substituição via `sed` do entrypoint
4. Simplificar docker-compose.yml para um único serviço nginx

## Conclusão

A solução é **viável e recomendada** para reduzir complexidade e consumo de recursos. O esforço é moderado e o resultado final será uma arquitetura mais limpa e eficiente.

**Recomendação:** Prosseguir com a implementação usando abordagem de template estático inicialmente, podendo evoluir para dinâmico se necessário.
