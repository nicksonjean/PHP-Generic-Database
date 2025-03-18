<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use GenericDatabase\Helpers\Zod\SchemaParser;
use GenericDatabase\Helpers\Zod\Zod\ZodError;

class SchemaParserStrategy
{
    public SchemaParser $instance;

    public function __construct(SchemaParser $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Valida os parâmetros para o método realConnect
     * 
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param int $port
     * @return array Dados validados
     * @throws \InvalidArgumentException
     */
    public function parse(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306
    ): array {
        $schema = $this->instance->createSchema();

        try {
            return $schema->parse([
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'database' => $database,
                'port' => $port,
            ]);
        } catch (ZodError $e) {
            throw new \InvalidArgumentException(
                "Parâmetros de conexão inválidos: " . $e->getMessage()
            );
        }
    }
}

// try {
//     $validatedData = MySQLConnectionValidator::validateRealConnectParams(
//         'localhost',
//         'root',
//         'senha123',
//         'meu_banco',
//         3306
//     );
    
//     // Uso dos dados validados
//     echo "Conexão validada com sucesso!\n";
//     echo "Host: " . $validatedData['host'] . "\n";
//     echo "Database: " . $validatedData['database'] . "\n";
    
// } catch (ZodError $e) {
//     // Exibe os erros de validação
//     echo "Erros de validação:\n";
//     foreach ($e->errors as $error) {
//         echo "- " . implode('.', $error['path']) . ": {$error['message']}\n";
//     }
// } catch (\Exception $e) {
//     echo "Erro: " . $e->getMessage() . "\n";
// }