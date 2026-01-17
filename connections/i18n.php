<?php
/**
 * Classe simples para internacionalizar sua aplicação em diversas linguagens, para cada linguagem existirá um arquivo Xml contendo as traduções.
 * O script é muito simples(porém eficiente), ele lê o arquivo Xml de tradução, faz um loop e adiciona no array($arrayLabel) os índices(atributo <name>) e seus valores(atributo <value>).
 * Através desse índice ele retorna a tradução da linguagem selecionada.
 * O padrão de nomenclatura dos arquivos Xmls serão esses:
 *  phpi18n.xml -> Linguagem padrão
 *  phpi18n_xx_XX.xml -> Onde xx_XX são a abreviação da linguagem e país.
 * Para maiores detalhes sobre abreviaturas dos países acessem(http://ftp.ics.uci.edu/pub/ietf/http/related/iso639.txt, http://userpage.chemie.fu-berlin.de/diverse/doc/ISO_3166.html, http://www.iso.org/iso/en/prods-services/iso3166ma/index.html).
 * Autor: Rodrigo Rodrigues
 * Email: web-rodrigo@bol.com.br
 * Versão: 1
 * IMPORTANTE: PRECISA TER INSTALADO O PHP 5 PORQUE USA O COMPONENTE SimpleXml(http://br.php.net/manual/pt_BR/ref.simplexml.php).
 */
class i18n
{
    /**
     * Variável Array privada com os valores da tradução.
     * @var array
     */
    private $arrayLabel = [];

    /**
     * Variável privada para armazenar o nome do arquivo XML completo.
     * @var string
     */
    private $xml;

    /**
     * Variável privada com o nome da linguagem.
     * @var string
     */
    private $language;

    /**
     * Variável privada com o nome do país.
     * @var string
     */
    private $country;

    /**
     * Método construtor para configurar a linguagem e o país de tradução, caso não encontre o arquivo Xml de tradução entra no default(Português).
     * @param string $xmlFile
     * @param string $language
     * @param string $country
     */
    public function __construct($xmlFile = "", $language = "", $country = "")
    {
        if (empty($xmlFile)) {
            $xmlFile = "en_US";
        }

        $this->language = $language;
        $this->country = $country;
        $this->xml = $xmlFile;

        if (!(empty($this->language) && empty($this->country))) {
            $this->xml = "./.locales/{$this->language}_{$this->country}";
        }

        $this->xml .= ".xml";
        $this->loadXml($this->xml);
    }

    /**
     * Método para carregar o xml da linguagem selecionada.
     * @param string $xml
     */
    private function loadXml($xml)
    {
        if (!file_exists($xml)) {
            $xml = "./.locales/en_US.xml";
        }

        $simpleXml = @simplexml_load_file($xml);

        if (!$simpleXml) {
            throw new Exception('There is no standard translation');
        }

        foreach ($simpleXml->label as $loadLabel) {
            $this->arrayLabel[(string) $loadLabel->name] = (string) $loadLabel->value;
        }
    }

    /**
     * Método que retorna o nome do arquivo Xml.
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Método que retorna o valor da tradução.
     * @param string $keyName
     * @return string
     */
    public function getLabel($keyName)
    {
        return $this->label($keyName);
    }

    /**
     * Método que retorna o valor da tradução com substituição de variáveis.
     * @param string $keyName Nome da chave de tradução
     * @param array $vars Array associativo com as variáveis para substituição
     * @return string
     */
    public function getLabelVars($keyName, array $vars = [])
    {
        $text = $this->label($keyName);

        if (empty($vars)) {
            return $text;
        }

        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($vars) {
            $key = $matches[1];
            return isset($vars[$key]) ? $vars[$key] : $matches[0];
        }, $text);
    }

    /**
     * Método privado que verifica se o parâmetro existe na chave(índice) do array, caso exista retorna seu valor.
     * @param string $keyName
     * @return string
     */
    private function label($keyName)
    {
        if ($this->arrayLabel == null || $this->arrayLabel[$keyName] == null) {
            return "empty";
        }

        return $this->arrayLabel[$keyName];
    }
}
