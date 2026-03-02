# Wizard — Alternativas de Formato para Arquivos de Dados

> Análise exploratória sobre substituição dos arquivos `.json` em `wizard/data/`
> por formatos binários que dificultem a leitura casual de dados sensíveis.

---

## Contexto

Os arquivos em `wizard/data/` (`profiles.json`, `queries.json`, `query_history.json`, etc.)
armazenam dados em JSON puro, incluindo credenciais de banco de dados (host, usuário, senha).
A ideia é substituí-los por um formato binário que:

- Não seja legível por humanos fora da interface (proteção de leitura casual)
- Mantenha compatibilidade com PHP 8.0+
- Não exija extensões obrigatórias (ou ofereça fallback puro PHP)

Formatos descartados por já serem suportados como engines pelo PHP Generic Database:
`json`, `yaml`, `xml`, `neon`, `csv`, `ini`.

---

## Candidatos Avaliados

### 1. MessagePack (`.msgpack`)

**Origem:** Especificação informal (Sadayuki Furuhashi, 2008), amplamente adotada.

**Como funciona:**
Serializa tipos nativos (null, bool, int, float, string, array/map) para bytes compactos.
Cada valor é prefixado por um byte de tipo + comprimento, sem separadores textuais.

**Exemplo de impacto no tamanho:**
```
{"username": "admin", "password": "s3cr3t", "host": "localhost"}
  → JSON:        ~55 bytes (texto, legível)
  → MessagePack: ~42 bytes (binário, ilegível)
```
A diferença cresce com listas longas — histórico de 100 queries teria redução perceptível.

**Implementação em PHP:**

Opção A — Extensão C nativa (PECL, recomendada para produção):
```bash
pecl install msgpack
```
```php
$bin = msgpack_pack(['username' => 'admin', 'password' => 's3cr3t']);
$arr = msgpack_unpack($bin);
```
Velocidade equivalente ao `json_encode/decode` nativo.

Opção B — Biblioteca pura PHP (sem extensão, funciona em qualquer PHP 8.0+):
```bash
composer require rybakit/msgpack
```
```php
use MessagePack\MessagePack;
$bin = MessagePack::pack(['username' => 'admin']);
$arr = MessagePack::unpack($bin);
```

**Migração dos JSONs atuais:**
```php
// Converte um arquivo existente
$data = json_decode(file_get_contents('profiles.json'), true);
file_put_contents('profiles.msgpack', msgpack_pack($data));
```

**Pontos fortes:**
- Tipos suportados cobrem 100% do que os JSONs do wizard usam
- Migração trivial: troca literal de `json_encode/decode` por `msgpack_pack/unpack`
- Arquivo aberto em editor de texto: exibe lixo binário — objetivo atingido
- Alta maturidade no ecossistema PHP

**Limitações:**
- Não tem tipos semânticos nativos (timestamp, UUID viram string/int sem marcação)
- Não é padrão formal (sem RFC IETF)

---

### 2. CBOR — Concise Binary Object Representation (`.cbor`)

**Origem:** RFC 7049 (2013), atualizado na RFC 8949 (2020). Padrão IETF formal.

**Como funciona:**
Similar ao MessagePack, mas com sistema de **tags numéricas** que permitem expressar
tipos semanticamente ricos: timestamps nativos, bignums, UUIDs, dados brutos, `undefined`, etc.

**Comparativo de tipos extras vs MessagePack:**

| Tipo | MessagePack | CBOR |
|---|---|---|
| `null` | ✅ | ✅ |
| `bool` | ✅ | ✅ |
| `int` / `float` | ✅ | ✅ |
| `string` / `bytes` | ✅ | ✅ |
| `array` / `map` | ✅ | ✅ |
| `datetime` nativo | ❌ (vira string) | ✅ (tag 1 = epoch) |
| `bignum` | ❌ | ✅ (tags 2-3) |
| `undefined` | ❌ | ✅ |
| Tags arbitrárias | Ext types (limitado) | Sistema de tags aberto |

**Impacto prático para o wizard:**
O campo `executed_at` do histórico passaria de 26 bytes (string ISO 8601)
para ~6 bytes (inteiro tagueado como epoch). Com 100 entradas no histórico,
a diferença é significativa.

**Implementação em PHP:**

Não existe extensão C estável no PECL. As opções são bibliotecas puras PHP:
```bash
composer require 2tvenom/cborencode
# ou
composer require spomky-labs/cbor-php
```

`spomky-labs/cbor-php` é a mais completa e mantida (suporte a todas as tags RFC 8949):
```php
use CBOR\CBORObject;
use CBOR\Encoder;
use CBOR\Decoder;

$encoded = Encoder::encode(['username' => 'admin', 'ts' => new DateTimeImmutable()]);
$decoded = Decoder::decode($encoded);
```

**Pontos fortes:**
- Padrão formal RFC com longevidade garantida
- Tipos semanticamente ricos — timestamps, bignums, tags customizadas
- Ideal se houver interoperabilidade futura com sistemas fora do PHP (IoT, APIs binárias)
- `undefined` distinto de `null` — útil para campos opcionais vs ausentes

**Limitações:**
- Sem extensão C nativa — depende de biblioteca PHP (overhead de parse maior)
- Tamanho ligeiramente maior que MessagePack por causa do overhead de tags
- Menos adotado no ecossistema PHP do que MessagePack
- Migração mais trabalhosa (API mais verbosa)

---

## Comparativo Final

| Critério | MessagePack | CBOR |
|---|---|---|
| PHP sem Composer | ✅ (extensão PECL) | ❌ |
| Velocidade de parse | Muito alta (ext nativa) | Média (biblioteca PHP) |
| Compactação vs JSON | ~30% menor | ~25% menor |
| Legibilidade humana | Nenhuma ✅ | Nenhuma ✅ |
| Tipos nativos ricos | ❌ | ✅ (datetime, bignum…) |
| Padrão formal (RFC) | ❌ | ✅ |
| Maturidade PHP | Alta | Média |
| Facilidade de migração | Trivial | Moderada |

---

## Conclusão / Recomendação

**Para o wizard em seu estado atual → MessagePack.**

Os dados armazenados são todos tipos simples (strings, arrays, inteiros, booleanos).
Nenhum tipo extra do CBOR seria aproveitado. A migração é uma troca literal de funções
em ~10 pontos do `api.php` e a extensão PECL existe desde PHP 5.x.

**CBOR valeria a pena se:**
- Fosse necessário armazenar timestamps com semântica nativa (sem string)
- Houvesse interoperabilidade com sistemas externos (IoT, APIs REST binárias)
- A longevidade de um padrão RFC formal fosse um requisito explícito

**Observação importante:**
Ambos os formatos atingem o objetivo principal —
um `cat` ou `notepad` no arquivo exibe lixo binário, protegendo credenciais
de uma leitura casual **sem necessitar de criptografia real**.
Para proteção de fato contra acesso não autorizado ao sistema de arquivos,
o complemento seria criptografia simétrica (AES-256-GCM via `openssl_encrypt`)
aplicada sobre o payload já serializado.

---

*Análise registrada em 2026-02-23. Retomar quando for iniciar a implementação.*
