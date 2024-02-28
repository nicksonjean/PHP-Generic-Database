<?php

namespace GenericDatabase\Tests\Core;

use PHPUnit\Framework\TestCase;

class ConstsTest extends TestCase
{
    public function testFirebirdConstants()
    {
        $this->assertSame(8, FIREBIRD_FETCH_NUM);
        $this->assertSame(9, FIREBIRD_FETCH_OBJ);
        $this->assertSame(10, FIREBIRD_FETCH_BOTH);
        $this->assertSame(11, FIREBIRD_FETCH_INTO);
        $this->assertSame(12, FIREBIRD_FETCH_CLASS);
        $this->assertSame(13, FIREBIRD_FETCH_ASSOC);
        $this->assertSame(14, FIREBIRD_FETCH_COLUMN);
    }

    public function testInterbaseConstants()
    {
        $this->assertSame(8, INTERBASE_FETCH_NUM);
        $this->assertSame(9, INTERBASE_FETCH_OBJ);
        $this->assertSame(10, INTERBASE_FETCH_BOTH);
        $this->assertSame(11, INTERBASE_FETCH_INTO);
        $this->assertSame(12, INTERBASE_FETCH_CLASS);
        $this->assertSame(13, INTERBASE_FETCH_ASSOC);
        $this->assertSame(14, INTERBASE_FETCH_COLUMN);
    }

    // Testes para outras constantes omitidos por brevidade

    public function testFetchConstants()
    {
        $this->assertSame(8, FETCH_NUM);
        $this->assertSame(9, FETCH_OBJ);
        $this->assertSame(10, FETCH_BOTH);
        $this->assertSame(11, FETCH_INTO);
        $this->assertSame(12, FETCH_CLASS);
        $this->assertSame(13, FETCH_ASSOC);
        $this->assertSame(14, FETCH_COLUMN);
    }
}
