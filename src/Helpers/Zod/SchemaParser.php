<?php

namespace GenericDatabase\Helpers\Zod;

use GenericDatabase\Helpers\Zod\Zod\Z;
use GenericDatabase\Helpers\Zod\Zod\ZodObject;
use GenericDatabase\Helpers\Zod\Zod\ZodToSchema;
use GenericDatabase\Helpers\Zod\Zod\ZodError;

class SchemaParser
{
    private string $schema;

    public function __construct(string $schemaJson)
    {
        $this->schema = $schemaJson;
    }


    /**
     * Cria um tipo Zod a partir da configuração JSON
     *
     * @param string $propertyName Nome da propriedade
     * @param array $config Configuração da propriedade
     * @param array $errorMessages Mensagens de erro personalizadas
     * @return mixed Instância de tipo Zod
     */
    private function createZodTypeFromJson(string $propertyName, array $config, array $errorMessages)
    {
        $type = $config['type'] ?? 'string';

        switch ($type) {
            case 'string':
                $zodType = Z::string();

                if (isset($config['minLength'])) {
                    $errorMsg = $errorMessages["$propertyName.minLength"] ?? null;
                    $zodType = $zodType->min($config['minLength'], $errorMsg);
                }

                if (isset($config['maxLength'])) {
                    $errorMsg = $errorMessages["$propertyName.maxLength"] ?? null;
                    $zodType = $zodType->max($config['maxLength'], $errorMsg);
                }

                if (isset($config['nullable']) && $config['nullable'] === true) {
                    $errorMsg = $errorMessages["$propertyName.nullable"] ?? null;
                    $zodType = $zodType->nullable($config['nullable'], $errorMsg);
                }

                if (isset($config['format']) && $config['format'] === 'email') {
                    $errorMsg = $errorMessages["$propertyName.format"] ?? 'Email inválido';
                    $zodType = $zodType->email($errorMsg);
                }

                if (isset($config['pattern'])) {
                    $errorMsg = $errorMessages["$propertyName.pattern"] ?? null;
                    $zodType = $zodType->regex($config['pattern'], $errorMsg);
                }
                return $zodType;

            case 'number':
                $zodType = Z::number();

                // Forçar tipo inteiro se especificado
                if (isset($config['integer']) && $config['integer'] === true) {
                    $zodType = $zodType->int();
                }

                if (isset($config['minimum'])) {
                    $errorMsg = $errorMessages["$propertyName.minimum"] ?? null;
                    $zodType = $zodType->min($config['minimum'], $errorMsg);
                }

                if (isset($config['maximum'])) {
                    $errorMsg = $errorMessages["$propertyName.maximum"] ?? null;
                    $zodType = $zodType->max($config['maximum'], $errorMsg);
                }

                return $zodType;

            case 'array':
                $zodType = Z::array();

                if (isset($config['items'])) {
                    $itemType = self::createZodTypeFromJson("$propertyName.items", $config['items'], $errorMessages);
                    $zodType = $zodType->of($itemType);
                }

                return $zodType;

            case 'boolean':
                return Z::boolean();

            default:
                throw new \InvalidArgumentException("Tipo não suportado: $type");
        }
    }

    /**
     * Converte um esquema Zod para JSON Schema
     *
     * @param ZodObject $zodSchema O esquema Zod a ser convertido
     * @param string|null $outputPath Caminho para salvar o JSON Schema gerado
     * @return array O JSON Schema gerado
     */
    private function createJsonFromZodFile(ZodObject $zodSchema, ?string $outputPath = null): array
    {
        // Usa nossa implementação personalizada para converter o esquema
        $zodToSchema = new ZodToSchema();
        $jsonSchema = $zodToSchema->generate($zodSchema);

        // Salva o esquema em um arquivo se especificado
        if ($outputPath) {
            file_put_contents($outputPath, json_encode($jsonSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $jsonSchema;
    }

    /**
     * Cria um esquema para validar os parâmetros do método realConnect
     * baseado no arquivo JSON de configuração
     *
     * @return ZodObject
     * @throws \RuntimeException Se o arquivo não for encontrado ou inválido
     */
    public function createSchema(): ZodObject
    {
        // Verificar se o arquivo existe
        if (!file_exists($this->schema)) {
            throw new \RuntimeException('Arquivo de esquema não encontrado: ' . $this->schema);
        }

        // Carregar e decodificar o arquivo JSON
        $schemaJson = file_get_contents($this->schema);
        $schema = json_decode($schemaJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erro ao decodificar arquivo JSON: ' . json_last_error_msg());
        }

        // Criar o esquema ZodObject com base no JSON
        $zodSchema = [];
        $errorMessages = $schema['errorMessages'] ?? [];

        foreach ($schema['properties'] as $propertyName => $propertyConfig) {
            $zodSchema[$propertyName] = $this->createZodTypeFromJson($propertyName, $propertyConfig, $errorMessages);

            // Adicionar valor padrão se especificado
            if (isset($propertyConfig['default'])) {
                $zodSchema[$propertyName] = $zodSchema[$propertyName]->default($propertyConfig['default']);
            }

            // Adicionar valor padrão se especificado
            if (isset($propertyConfig['nullable'])) {
                $zodSchema[$propertyName] = $zodSchema[$propertyName]->nullable($propertyConfig['nullable']);
            }

            // Adicionar descrição se especificada
            if (isset($propertyConfig['description'])) {
                $zodSchema[$propertyName] = $zodSchema[$propertyName]->describe($propertyConfig['description']);
            }
        }

        return Z::object($zodSchema);
    }

    /**
     * Valida os parâmetros para o método realConnect
     *
     * @param array $params
     * @return array Dados validados
     * @throws \InvalidArgumentException
     */
    public function parse(array $params): array
    {
        $schema = $this->createSchema();
        try {
            return $schema->parse($params);
        } catch (ZodError $e) {
            throw new \InvalidArgumentException(
                "Parâmetros de conexão inválidos: " . $e->getMessage()
            );
        }
    }

    /**
     * Exporta o esquema de conexão MySQL para JSON Schema
     *
     * @param string $outputPath Caminho do arquivo de saída
     * @return array O JSON Schema gerado
     */
    public function export(string $outputPath): array
    {
        $schema = $this->createSchema();
        try {
            return $this->createJsonFromZodFile($schema, $outputPath);
        } catch (ZodError $e) {
            throw new \InvalidArgumentException(
                "Parâmetros de conexão inválidos: " . $e->getMessage()
            );
        }
    }
}
