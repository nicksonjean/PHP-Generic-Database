<?php

namespace GenericDatabase\Helpers\Zod\Zod;

/**
 * Classe principal para criar tipos de validação
 */
class Z
{
    public static function string(): ZodString
    {
        return new ZodString();
    }

    public static function number(): ZodNumber
    {
        return new ZodNumber();
    }

    public static function boolean(): ZodBoolean
    {
        return new ZodBoolean();
    }

    public static function array(): ZodArray
    {
        return new ZodArray();
    }

    public static function object(array $shape): ZodObject
    {
        return new ZodObject($shape);
    }

    public static function null(): ZodNull
    {
        return new ZodNull();
    }
}

