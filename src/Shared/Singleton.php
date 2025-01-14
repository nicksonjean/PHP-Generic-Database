<?php

/** @noinspection ALL */

/** @noinspection ALL */

namespace GenericDatabase\Shared;

trait Singleton
{
    /**
     * Static instance for classic singleton pattern
     */
    private static ?self $instance = null;

    /**
     * Array to store multiple instances by hash
     */
    private static array $instances = [];

    /**
     * Create or obtain an instance
     * If no hash is provided, works as classic singleton
     * If hash is provided, creates/returns instance for that hash
     *
     * @param string|null $hash Optional unique identifier for multiple instances
     * @return self
     */
    public static function getInstance(?string $hash = null): self
    {
        if ($hash === null) {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        if (!isset(self::$instances[$hash])) {
            self::$instances[$hash] = new self();
        }
        return self::$instances[$hash];
    }

    /**
     * Create a new instance of the class and set it as the instance
     *
     * @param string|null $hash Optional unique identifier for multiple instances
     * @return self The newly created instance
     */
    public static function newInstance(?string $hash = null): self
    {
        if ($hash === null) {
            self::$instance = new self();
            return self::$instance;
        }

        self::$instances[$hash] = new self();
        return self::$instances[$hash];
    }

    /**
     * Set a specific instance
     *
     * @param self $instance The instance to set
     * @param string|null $hash Optional unique identifier for multiple instances
     * @return void
     */
    public static function setInstance(self $instance, ?string $hash = null): void
    {
        if ($hash === null) {
            self::$instance = $instance;
            return;
        }

        self::$instances[$hash] = $instance;
    }

    /**
     * Clear instance(s)
     *
     * @param string|null $hash Optional unique identifier for multiple instances
     * @return void
     */
    public static function clearInstance(?string $hash = null): void
    {
        if ($hash === null) {
            self::$instance = null;
            return;
        }

        unset(self::$instances[$hash]);
    }

    /**
     * Clear all instances including the classic singleton instance
     *
     * @return void
     */
    public static function clearAllInstances(): void
    {
        self::$instance = null;
        self::$instances = [];
    }
}
