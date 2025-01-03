<?php
class Autoloader
{
    private static $loadedVariables = [];

    public static function load(...$params)
    {
        $includeFileWithVars = function ($file, $vars) {
            $uniqueKey = realpath($file);
            self::$loadedVariables[$uniqueKey] = $vars;
            include_once($file);
        };

        foreach ($params as $param) {
            $folder = $param[0];
            $files = is_array($param[1]) ? $param[1] : [$param[1]];
            $vars = isset($param[2]) ? $param[2] : [];
            $baseDir = __DIR__ . '/' . $folder . '/';

            foreach ($files as $file) {
                $filePath = $baseDir . $file . '.php';

                if (file_exists($filePath)) {
                    $includeFileWithVars($filePath, $vars);
                } else {
                    echo "Arquivo não encontrado: $filePath";
                }
            }
        }
    }

    public static function getLoadedVariables($uniqueKey)
    {
        return isset(self::$loadedVariables[$uniqueKey]) ? self::$loadedVariables[$uniqueKey] : null;
    }
}

if (!function_exists('getVars')) {
    function getVars()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $currentFile = realpath($backtrace[0]['file']);
        return Autoloader::getLoadedVariables($currentFile);
    }
}
?>
