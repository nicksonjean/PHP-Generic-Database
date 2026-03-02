<?php

/**
 * Simple Template Engine - No Composer required
 * Uses PHP native include with variable extraction
 */

declare(strict_types=1);

class Template
{
    private string $basePath;
    private array $vars = [];

    /**
     * Construtor da classe Template.
     *
     * @param string $basePath Caminho base para os templates.
     * @return void
     */
    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath ?: __DIR__ . '/../templates';
    }

    /**
     * Assigna um valor a uma variável.
     *
     * @param string $key Chave da variável.
     * @param mixed $value Valor da variável.
     * @return self Retorna a instância da classe.
     */
    public function assign(string $key, mixed $value): self
    {
        $this->vars[$key] = $value;
        return $this;
    }

    /**
     * Assigna múltiplos valores a variáveis.
     *
     * @param array $vars Array associativo com as variáveis e seus valores.
     * @return self Retorna a instância da classe.
     */
    public function assignMultiple(array $vars): self
    {
        $this->vars = array_merge($this->vars, $vars);
        return $this;
    }

    /**
     * Renderiza um template.
     *
     * @param string $template Caminho do template a ser renderizado.
     * @param array $vars Array associativo com as variáveis e seus valores.
     * @return string Retorna o conteúdo renderizado do template.
     */
    public function render(string $template, array $vars = []): string
    {
        $path = $this->basePath . '/' . ltrim($template, '/');
        if (!file_exists($path)) {
            throw new \RuntimeException("Template not found: {$path}");
        }
        $data = array_merge($this->vars, $vars);
        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
