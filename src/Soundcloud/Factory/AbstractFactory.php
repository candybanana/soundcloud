<?php

namespace Njasm\Soundcloud\Factory;

use Njasm\Soundcloud\Collection\Collection;
use Njasm\Soundcloud\Exception\SoundcloudResponseException;
use Njasm\Soundcloud\Resolve\Resolve;
use Njasm\Soundcloud\Soundcloud;

class AbstractFactory
{
    public static function unserialize($serialized)
    {
        $data = json_decode($serialized, true);

        self::guardAgainstErrors($data);

        if (isset($data['status'])) {
            return self::resolve($data);
        }

        if (isset($data[0]) && is_array($data[0])) {
            $collection = self::collection($data[0]['kind']);

            return self::addItemsToCollection($collection, $data);
        }

        return self::resource($serialized);
    }

    protected static function guardAgainstErrors(array $data)
    {
        if (isset($data['errors'])) {
            throw new SoundcloudResponseException($data);
        }
    }

    public static function collection($kind)
    {
        $kind = str_replace("-", "", $kind);
        $collectionClass = "\\Njasm\\Soundcloud\\Collection\\" . ucfirst($kind) . "Collection";
        if (class_exists($collectionClass)) {
            return new $collectionClass;
        }

        throw new \Exception("$collectionClass non-existent.");
    }

    protected static function addItemsToCollection(Collection $collection, array $data)
    {
        foreach($data as $line) {
            $resource = self::resource($line);
            $collection->add($resource);
        }

        return $collection;
    }

    public static function resource($line)
    {
        if (!is_array($line)) {
            $line = json_decode($line, true);
        }

        $sc = Soundcloud::instance();
        $line['kind'] = str_replace("-", "", $line['kind']);
        $resourceClass = "\\Njasm\\Soundcloud\\Resource\\" . ucfirst($line['kind']);
        $reflectionResource = new \ReflectionClass($resourceClass);

        /** @var \Njasm\Soundcloud\Resource\AbstractResource $resource */
        $resource = $reflectionResource->newInstanceArgs(array($sc));
        $resource->unserialize($line);

        return $resource;
    }

    public static function resolve($data)
    {
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        return new Resolve($data['status'], $data['location']);
    }
}