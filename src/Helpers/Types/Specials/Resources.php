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
            is_array($resource) => md5(serialize($resource)),
            default => 'null',
        };
    }
}
