<?php


namespace Sigmasolutions\Sheets\Tests\Unit;


class Utility
{
    private const RESOURCE_DIRECTORY = 'tests/resources';

    public static function getResourcePath($resourceName, $reType = null): ?string
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $resourceType = !empty($resourceType) ? $resourceType : $reType;
        $resourcePath = realpath(static::RESOURCE_DIRECTORY) . '/' . strtolower($resourceType) . '/' . $resourceName;
        return (file_exists($resourcePath) ? $resourcePath : null);
    }
}