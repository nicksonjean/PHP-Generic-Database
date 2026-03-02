<?php
/**
 * Simple Translator - Professional i18n without Composer
 * Supports English and Portuguese
 */
declare(strict_types=1);

class Translator
{
    private array $labels = [];
    private string $locale = 'en';

    /**
     * Construtor da classe Translator.
     *
     * @param string $locale Idioma a ser usado.
     * @return void
     */
    public function __construct(string $locale = 'en')
    {
        $this->locale = in_array($locale, ['en', 'pt']) ? $locale : 'en';
        $file = __DIR__ . '/../locales/' . $this->locale . '.php';
        if (file_exists($file)) {
            $this->labels = require $file;
        }
    }

    /**
     * Traduz uma chave de texto.
     *
     * @param string $key Chave de texto.
     * @param array $vars Array associativo com as variáveis para substituição.
     * @return string Retorna o texto traduzido.
     */
    public function t(string $key, array $vars = []): string
    {
        $text = $this->labels[$key] ?? $key;
        if (empty($vars)) {
            return $text;
        }
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($vars) {
            return $vars[$m[1]] ?? $m[0];
        }, $text);
    }

    /**
     * Retorna o idioma atual.
     *
     * @return string Retorna o idioma atual.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Retorna o idioma atual para o HTML.
     *
     * @return string Retorna o idioma atual para o HTML.
     */
    public function getLocaleForHtml(): string
    {
        return $this->locale === 'pt' ? 'pt-BR' : 'en';
    }
}
