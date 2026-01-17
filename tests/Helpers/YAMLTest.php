<?php

namespace GenericDatabase\Tests\Helpers;

use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\Path;
use PHPUnit\Framework\TestCase;

final class YAMLTest extends TestCase
{
    public function testValidYamlString()
    {
        $yaml = 'tests/Helpers/Samples/yaml/Complex.yaml';
        $isValid = YAML::isValidYAML(Path::toAbsolute($yaml));
        $this->assertTrue($isValid);
    }

    public function testEmptyYamlString()
    {
        $yaml = "";
        $isValid = YAML::isValidYAML($yaml);
        $this->assertFalse($isValid);
    }

    public function testYamlStringWithComments()
    {
        $yaml = "# This is a comment";
        $isValid = YAML::isValidYAML($yaml);
        $this->assertFalse($isValid);
    }

    public function testNonStringArgument()
    {
        $yaml = 123;
        $isValid = YAML::isValidYAML($yaml);
        $this->assertFalse($isValid);
    }

    public function testYamlStringNotEndingWithYaml()
    {
        $yaml = "name: John\nage: 30";
        $isValid = YAML::isValidYAML($yaml);
        $this->assertFalse($isValid);
    }

    public function testToYamlParser()
    {
        $expected = <<<END
foo: bar
pleh: help
stuff:
  foo: bar
  bar: foo
END;
        $yaml = 'tests/Helpers/Samples/yaml/Simple.yaml';
        $parser = YAML::parseYAML($yaml);
        $this->assertEquals(yaml_parse($expected), $parser);
    }
}
