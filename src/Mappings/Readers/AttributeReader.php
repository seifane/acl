<?php

namespace LaravelDoctrine\ACL\Mappings\Readers;

use Attribute;
use Gedmo\Mapping\Annotation\Annotation;
use ReflectionClass;

final class AttributeReader
{
    /** @var array<string,bool> */
    private $isRepeatableAttribute = [];

    /**
     * @return array<Annotation|Annotation[]>
     */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @phpstan-param class-string $annotationName
     *
     * @return Annotation|Annotation[]|null
     */
    public function getClassAnnotation(ReflectionClass $class, string $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? null;
    }

    /**
     * @return array<Annotation|Annotation[]>
     */
    public function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @phpstan-param class-string $annotationName
     *
     * @return Annotation|Annotation[]|null
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, string $annotationName)
    {
        return $this->getPropertyAnnotations($property)[$annotationName] ?? null;
    }

    /**
     * @param array<\ReflectionAttribute> $attributes
     *
     * @return array<string, Annotation|Annotation[]>
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));
            // Make sure we only get Gedmo Annotations
            if (!is_subclass_of($attributeName, Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof Annotation);

            if ($this->isRepeatable($attributeName)) {
                if (!isset($instances[$attributeName])) {
                    $instances[$attributeName] = [];
                }

                $instances[$attributeName][] = $instance;
            } else {
                $instances[$attributeName] = $instance;
            }
        }

        return $instances;
    }

    private function isRepeatable(string $attributeClassName): bool
    {
        if (isset($this->isRepeatableAttribute[$attributeClassName])) {
            return $this->isRepeatableAttribute[$attributeClassName];
        }

        $reflectionClass = new ReflectionClass($attributeClassName);
        $attribute = $reflectionClass->getAttributes()[0]->newInstance();

        return $this->isRepeatableAttribute[$attributeClassName] = ($attribute->flags & Attribute::IS_REPEATABLE) > 0;
    }
}
