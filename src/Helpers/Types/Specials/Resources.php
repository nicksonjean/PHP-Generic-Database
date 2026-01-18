<?php

namespace GenericDatabase\Helpers\Types\Specials;

/**
 * The `GenericDatabase\Helpers\Types\Specials\Resources` trait provides methods for working with resources.
 *
 * @package GenericDatabase\Helpers\Types\Specials
 * @subpackage Resources
 */
trait Resources
{
    /**
     * Gets a unique identifier for the given resource.
     *
     * @param mixed $resource The resource to get the identifier for.
     *                        It can be of type resource, object, or array.
     *
     * @return string A unique identifier for the resource. If the resource is a
     *                resource type, it returns its string representation. If it's
     *                an object, it returns the object's hash. If it's an array, it
     *                returns the MD5 hash of the serialized array. Returns 'null'
     *                if the resource type is not recognized.
     */
    public function getResourceId(mixed $resource): string
    {
        return match (true) {
            is_resource($resource) => (string)$resource,
            is_object($resource) => spl_object_hash($resource),
            is_array($resource) => $this->getArrayResourceId($resource),
            default => 'null',
        };
    }

    /**
     * Gets a unique identifier for an array resource, handling non-serializable objects.
     *
     * @param array $resource The array resource to get the identifier for.
     * @return string A unique identifier for the array resource.
     */
    private function getArrayResourceId(array $resource): string
    {
        $serializable = $this->makeArraySerializable($resource);
        return md5(serialize($serializable));
    }

    /**
     * Converts an array containing non-serializable ODBC objects to a serializable format.
     *
     * @param array $array The array to convert.
     * @return array The serializable array representation.
     */
    private function makeArraySerializable(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->makeArraySerializable($value);
            } elseif (is_object($value)) {
                $className = get_class($value);
                if (
                    PHP_VERSION_ID >= 80400 && (
                    $className === 'Odbc\Result' ||
                    $className === 'Odbc\Connection'
                    )
                ) {
                    $result[$key] = ['__odbc_object__' => $className, '__hash__' => spl_object_hash($value)];
                } else {
                    $result[$key] = ['__object__' => $className, '__hash__' => spl_object_hash($value)];
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
