<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Types\ArrayType as OpenApiArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeWalker;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class DataCollectionTypeToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type)
    {
        return $type instanceof Generic
            && $type->isInstanceOf(DataCollection::class);
    }

    public function toSchema(Type $type)
    {
        if (! $collectingResourceType = $this->getCollectingResourceType($type)) {
            return null;
        }

        return (new OpenApiArrayType)
            ->setItems($this->openApiTransformer->transform($collectingResourceType));
    }

    private function getCollectingResourceType(Generic $type): ?ObjectType
    {
        // In case of paginated resource, we still want to get to the underlying JsonResource.
        return (new TypeWalker)->first(
            $type->templateTypes[0],
            fn (Type $t) => $t->isInstanceOf(Data::class),
        );
    }
}
