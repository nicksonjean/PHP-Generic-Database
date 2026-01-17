<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\XML;
use GenericDatabase\Helpers\Path;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

final class XMLTest extends TestCase
{
    public function testValidXmlReturnsTrue()
    {
        $xml = 'tests/Helpers/Samples/xml/Simple.xml';
        $result = XML::isValidXML(Path::toAbsolute($xml));
        $this->assertTrue($result);
    }

    public function testValidXmlReturnsFalse()
    {
        $xml = 123;
        $result = XML::isValidXML($xml);
        $this->assertFalse($result);

        $xml = 'tests/Helpers/Samples/xml/Invalid.xml';
        $result = XML::isValidXML($xml);
        $this->assertFalse($result);
    }


    /**
     * @throws ReflectionException
     */
    public function testConvertedToAppropriateType()
    {
        $reflectionClass = new ReflectionClass(XML::class);
        $method = $reflectionClass->getMethod('convertData');
        $method->setAccessible(true); //NOSONAR

        $data = '123';
        $result = $method->invokeArgs(null, [$data]);
        $this->assertSame(123, $result);

        $data = '123.45';
        $result = $method->invokeArgs(null, [$data]);
        $this->assertSame(123.45, $result);

        $data = 'false';
        $result = $method->invokeArgs(null, [$data]);
        $this->assertFalse($result);

        $data = 'true';
        $result = $method->invokeArgs(null, [$data]);
        $this->assertTrue($result);
    }

    /**
     * @throws ReflectionException
     */
    public function testSimpleXmlElementObjectIsDecodedIntoArray()
    {
        $xml = 'tests/Helpers/Samples/xml/Easy.xml';
        $objXML = simplexml_load_file($xml);

        $reflectionClass = new ReflectionClass(XML::class);
        $method = $reflectionClass->getMethod('decodeXML');
        $method->setAccessible(true); //NOSONAR

        $result = $method->invokeArgs(null, [$objXML]);

        $expected = [
            'element1' => 'value1',
            'element2' => 'value2'
        ];

        $this->assertSame($expected, $result);
    }

    public function testParseXml()
    {
        $xml = 'tests/Helpers/Samples/xml/Parsed.xml';
        $result = XML::parseXML($xml);

        $expected = [
            'tagA' => 'AAA',
            'tagB' => 'bbb',
            'options' => [
                'opcao1' => true,
                'opcao2' => 42,
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
