<?php

$GLOBALS['loadedVariables'] = [];

class Autoloader
{

    /**
     * Renderiza um arquivo com variáveis no escopo e retorna a saída como string.
     *
     * @param string $file Caminho para o arquivo PHP a ser processado.
     * @param array $vars Variáveis a serem injetadas no escopo do arquivo.
     * @return string O conteúdo renderizado do arquivo.
     * @throws Exception Se o arquivo não for encontrado.
     */
    public static function renderFile(string $file, array $vars = []): string
    {
        if (!file_exists($file)) {
            throw new Exception("Arquivo não encontrado: $file");
        }

        // Extrai as variáveis no escopo local
        extract($vars);

        // Inicia o buffer de saída
        ob_start();

        // Inclui o arquivo
        include $file;

        // Captura o conteúdo do buffer
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Carrega os arquivos com base em uma matriz no formato ajustado.
     *
     * @param array $config Configuração no formato ajustado.
     */
    public static function loadFromArray(array $config)
    {
        foreach ($config as $item) {
            $folder = $item['path'] ?? null;
            $files = $item['files'] ?? [];
            $vars = $item['vars'] ?? [];

            if (!$folder || empty($files)) {
                continue; // Ignora entradas inválidas.
            }

            $baseDir = __DIR__ . '/' . $folder . '/';

            foreach ((array) $files as $file) {
                $filePath = $baseDir . $file . '.php';

                if (file_exists($filePath)) {
                    self::includeFileWithVars($filePath, $vars);
                } else {
                    echo "Arquivo não encontrado: $filePath\n";
                }
            }
        }
    }

    /**
     * Carrega a configuração a partir de um arquivo JSON no mesmo formato.
     *
     * @param string $jsonFilePath Caminho para o arquivo JSON.
     * @throws Exception Se o arquivo JSON não for encontrado ou inválido.
     */
    public static function loadFromJson(string $jsonFilePath)
    {
        if (!file_exists($jsonFilePath)) {
            throw new Exception("Arquivo de configuração JSON não encontrado: $jsonFilePath");
        }

        $jsonContent = file_get_contents($jsonFilePath);
        $config = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar o arquivo JSON: " . json_last_error_msg());
        }

        self::loadFromArray($config);
    }

    /**
     * Inclui o arquivo e injeta as variáveis no escopo.
     *
     * @param string $file Caminho do arquivo.
     * @param array $vars Variáveis a serem injetadas.
     */
    private static function includeFileWithVars(string $file, array $vars)
    {
        $uniqueKey = realpath($file);
        $GLOBALS['loadedVariables'][$uniqueKey] = $vars;

        extract($vars);
        include_once($file);
    }

    /**
     * Retorna as variáveis carregadas para um arquivo específico.
     *
     * @param string $uniqueKey Caminho único do arquivo.
     * @return array|null Variáveis carregadas ou null se não existir.
     */
    public static function getLoadedVariables(string $uniqueKey = ''): ?array
    {
        if (empty($uniqueKey)) { {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                $currentFile = realpath($backtrace[0]['file']);
                return $GLOBALS['loadedVariables'][$currentFile] ?? null;
            }
        }
        return $GLOBALS['loadedVariables'][$uniqueKey] ?? null;
    }
}

// Função global para recuperar variáveis carregadas.
if (!function_exists('getVars')) {
    function getVars()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $currentFile = realpath($backtrace[0]['file']);
        return Autoloader::getLoadedVariables($currentFile);
    }
}
