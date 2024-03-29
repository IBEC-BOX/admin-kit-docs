<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Data;
use Illuminate\Support\Collection;
use Dedoc\Scramble\Support\Type\Type;
use Spatie\LaravelData\DataCollection;
use Dedoc\Scramble\Support\Generator\Types;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;

class DataTypeToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type)
    {
        return $type->isInstanceOf(Data::class);
    }

    /**
     * @throws \ReflectionException
     */
    public function toSchema(Type $type): Types\ObjectType
    {
        return $this->getClassTransformedTypes($type->name);
    }

    /**
     * @throws \ReflectionException
     */
    protected function getClassTransformedTypes(string $class): Types\ObjectType
    {
        $reflection = new ReflectionClass($class);

        $object = new Types\ObjectType();

        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $object->addProperty(
                name: $property->getName(),
                propertyType: $this->transformPropertyToOpenApiType($property)
            );
        }

        return $object;
    }

    /**
     * @throws \ReflectionException
     */
    protected function transformPropertyToOpenApiType(ReflectionProperty $property): Types\Type
    {
        $type = $property->getType();

        if (!$type) {
            return (new Types\StringType())
                ->nullable(true);
        }

        $typeName = $type->getName();

        if ($typeName === 'string') {
            return (new Types\StringType())
                ->nullable($type->allowsNull());
        }

        if (in_array($typeName, ['int', 'float'])) {
            return (new Types\NumberType())
                ->nullable($type->allowsNull());
        }

        if ($typeName === 'bool') {
            return (new Types\BooleanType())
                ->nullable($type->allowsNull());
        }

        if ($typeName === 'array') {
            return (new Types\ArrayType())
                ->nullable($type->allowsNull());
        }

        if ($typeName === Collection::class) {
            return (new Types\ArrayType())
                ->nullable($type->allowsNull());
        }

        if ($typeName === DataCollection::class) {
            $type = (new Types\ArrayType())
                ->nullable($type->allowsNull());

            foreach ($property->getAttributes() as $attribute) {
                if ($attribute->getName() === DataCollectionOf::class) {
                    $collectionClass = $attribute->getArguments()[0];

                    $object = $this->getClassTransformedTypes($collectionClass);

                    $type->setItems($object);
                }
            }

            return $type;
        }

        return (new Types\UnknownType());
    }
}
