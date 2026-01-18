<?php

namespace GenericDatabase\Helpers\Zod;

class SchemaValidator
{
    private $schema;
    private $errorMessages = [];
    private $errors = [];

    public function __construct(string $schemaJson)
    {
        $this->schema = json_decode(file_get_contents($schemaJson), true);
        if (isset($this->schema['errorMessages'])) {
            $this->errorMessages = $this->schema['errorMessages'];
        }
    }

    public function validate(string|array $jsonData): bool
    {
        $this->errors = [];
        if (is_string($jsonData)) {
            $data = json_decode($jsonData, true);
        } else {
            $data = $jsonData;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = "JSON inválido: " . json_last_error_msg();
            return false;
        }

        return $this->validateSchema($data, $this->schema, '');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function validateSchema($data, $schema, $path): bool
    {
        // Validar tipo do schema principal
        if (isset($schema['type'])) {
            if (!$this->validateType($data, $schema['type'], $path)) {
                return false;
            }
        }

        // Validar propriedades se for um objeto
        if (isset($schema['type']) && $schema['type'] === 'object' && isset($schema['properties'])) {
            return $this->validateObject($data, $schema, $path);
        }

        // Validar itens se for um array
        if (isset($schema['type']) && $schema['type'] === 'array' && isset($schema['items'])) {
            return $this->validateArray($data, $schema, $path);
        }

        return true;
    }

    private function validateType($value, $type, $path): bool
    {
        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    $this->addError($path, "deve ser uma string");
                    return false;
                }
                break;
            case 'number':
                if (!is_numeric($value)) {
                    $this->addError($path, "deve ser um número");
                    return false;
                }
                break;
            case 'integer':
                if (!is_int($value) && (!is_numeric($value) || (int)$value != $value)) {
                    $this->addError($path, "deve ser um número inteiro");
                    return false;
                }
                break;
            case 'object':
                if (!is_array($value) || array_keys($value) !== range(0, count($value) - 1)) {
                    return true; // É um objeto (array associativo)
                }
                $this->addError($path, "deve ser um objeto");
                return false;
            case 'array':
                if (!is_array($value) || array_keys($value) !== range(0, count($value) - 1)) {
                    $this->addError($path, "deve ser um array");
                    return false;
                }
                break;
            case 'null':
                if ($value !== null) {
                    $this->addError($path, "deve ser nulo");
                    return false;
                }
                break;
        }
        return true;
    }

    private function validateObject($data, $schema, $path): bool
    {
        $isValid = true;

        // Verificar propriedades obrigatórias
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $requiredProp) {
                if (!isset($data[$requiredProp])) {
                    $propPath = $path ? "$path.$requiredProp" : $requiredProp;
                    $this->addError($propPath, "é obrigatório", "$propPath.required");
                    $isValid = false;
                }
            }
        }

        // Validar cada propriedade definida
        foreach ($schema['properties'] as $propName => $propSchema) {
            $propPath = $path ? "$path.$propName" : $propName;

            // Pular validação se a propriedade não está presente e não é obrigatória
            if (!isset($data[$propName])) {
                continue;
            }

            // Validar tipo da propriedade
            if (isset($propSchema['type'])) {
                if (!$this->validateType($data[$propName], $propSchema['type'], $propPath)) {
                    $isValid = false;
                    continue;
                }
            }

            // Validar comprimento mínimo para strings
            if (isset($propSchema['type']) && $propSchema['type'] === 'string' && isset($propSchema['minLength'])) {
                if (strlen($data[$propName]) < $propSchema['minLength']) {
                    $this->addError($propPath, "deve ter pelo menos {$propSchema['minLength']} caracteres", "$propPath.minLength");
                    $isValid = false;
                }
            }

            // Validar valor mínimo para números
            if ((isset($propSchema['type']) && ($propSchema['type'] === 'number' || $propSchema['type'] === 'integer')) && isset($propSchema['minimum'])) {
                if ($data[$propName] < $propSchema['minimum']) {
                    $this->addError($propPath, "deve ser maior ou igual a {$propSchema['minimum']}", "$propPath.minimum");
                    $isValid = false;
                }
            }

            // Validar valor máximo para números
            if ((isset($propSchema['type']) && ($propSchema['type'] === 'number' || $propSchema['type'] === 'integer')) && isset($propSchema['maximum'])) {
                if ($data[$propName] > $propSchema['maximum']) {
                    $this->addError($propPath, "deve ser menor ou igual a {$propSchema['maximum']}", "$propPath.maximum");
                    $isValid = false;
                }
            }

            // Validação recursiva para objetos aninhados
            if (isset($propSchema['type']) && $propSchema['type'] === 'object' && isset($propSchema['properties'])) {
                if (!$this->validateObject($data[$propName], $propSchema, $propPath)) {
                    $isValid = false;
                }
            }

            // Validação recursiva para arrays
            if (isset($propSchema['type']) && $propSchema['type'] === 'array' && isset($propSchema['items'])) {
                if (!$this->validateArray($data[$propName], $propSchema, $propPath)) {
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }

    private function validateArray($data, $schema, $path): bool
    {
        $isValid = true;

        for ($i = 0; $i < count($data); $i++) {
            $itemPath = "$path[$i]";
            if (!$this->validateSchema($data[$i], $schema['items'], $itemPath)) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    private function addError($path, $message, $errorKey = null): void
    {
        // Se temos uma mensagem de erro personalizada para esta chave, use-a
        if ($errorKey && isset($this->errorMessages[$errorKey])) {
            $this->errors[] = $this->errorMessages[$errorKey];
        } else {
            $this->errors[] = "O campo '$path' $message";
        }
    }
}

/*
class JsonSchemaExample
{
    public static function main(): void
    {
        // Exemplo de schema
        $schemaJson = '{
            "type": "object",
            "properties": {
                "host": {
                    "type": "string",
                    "minLength": 1,
                    "description": "Endereço do servidor MySQL"
                },
                "user": {
                    "type": "string",
                    "minLength": 1,
                    "description": "Nome do usuário para conexão"
                },
                "password": {
                    "type": "string",
                    "description": "Senha do usuário para conexão"
                },
                "database": {
                    "type": "string",
                    "minLength": 1,
                    "description": "Nome do banco de dados"
                },
                "port": {
                    "type": "number",
                    "minimum": 1,
                    "maximum": 65535,
                    "default": 3306,
                    "description": "Porta do servidor MySQL"
                }
            },
            "required": ["host", "user", "password", "database"],
            "errorMessages": {
                "host.minLength": "O host é obrigatório",
                "user.minLength": "O usuário é obrigatório",
                "database.minLength": "O nome do banco de dados é obrigatório",
                "port.minimum": "A porta deve ser maior que 0",
                "port.maximum": "A porta deve ser menor que 65536"
            }
        }';

        // Dados válidos
        $validJson = '{
            "host": "localhost",
            "user": "root",
            "password": "secret",
            "database": "mydb",
            "port": 3306
        }';

        // Dados inválidos
        $invalidJson = '{
            "host": "",
            "user": "admin",
            "database": "",
            "port": 70000
        }';

        // Validar dados
        $validator = new SchemaValidator($schemaJson);

        echo "Validando dados válidos:\n";
        if ($validator->validate($validJson)) {
            echo "Dados válidos!\n";
        } else {
            echo "Erros encontrados:\n";
            foreach ($validator->getErrors() as $error) {
                echo "- $error\n";
            }
        }

        echo "\nValidando dados inválidos:\n";
        if ($validator->validate($invalidJson)) {
            echo "Dados válidos!\n";
        } else {
            echo "Erros encontrados:\n";
            foreach ($validator->getErrors() as $error) {
                echo "- $error\n";
            }
        }
    }
}
*/
// Para testar o exemplo
// JsonSchemaExample::main();
