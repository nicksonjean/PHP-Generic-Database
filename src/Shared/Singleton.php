<?php

namespace GenericDatabase\Shared;

/**
 * This trait provides methods to manage a single instance of a class or multiple
 * instances identified by a unique hash. It includes methods to get, create, set,
 * and clear instances.
 *
 * Methods:
 * - `getInstance(): self:` Retrieves an existing instance or creates a new one.
 * - `newInstance(): self:` Creates a new instance and sets it as the current instance.
 * - `setInstance(): void:` Sets a specific instance, optionally identified by a hash.
 * - `clearInstance(): void:` Clears a specific instance or the default instance.
 * - `clearAllInstances(): void:` Clears all instances, including the default one.
 *
 * Fields:
 * - `$instance`: Static instance for classic singleton pattern.
 * - `$instances`: Array to store multiple instances by hash.
 */
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
