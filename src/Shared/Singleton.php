<?php

/** @noinspection ALL */

/** @noinspection ALL */

namespace GenericDatabase\Shared;

trait Singleton
{
    /**
     * Static class instance
     */
    private static ?self $instance = null;

    /**
     * Create or obtain a singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create or obtain a singleton instance
     *
     * @param mixed ...$params Optional parameters to pass to the constructor
     * @return self
     */
    public static function getInstanceWithParams(...$params): self
    {
        if (!self::$instance) {
            self::$instance = new self(...$params);
        }
        return self::$instance;
    }

    /**
     * Create a new instance of the class and set it as the singleton instance.
     *
     * @return self The newly created singleton instance.
     */
    public static function newInstance(): self
    {
        self::$instance = new self();
        return self::$instance;
    }

    /**
     * Create or obtain a singleton instance
     *
     * @param $instance
     * @return void
     */
    public static function setInstance($instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Clear a singleton instance
     *
     * @return void
     */
    public static function clearInstance(): void
    {
        self::$instance = null;
    }
}
