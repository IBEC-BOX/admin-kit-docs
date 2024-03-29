<?php

namespace Dedoc\Scramble\Support\InferExtensions;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Type;
use PhpParser\Node;
use PhpParser\Node\Expr;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class DataCreationInfer implements ExpressionTypeInferExtension
{
    public function getType(Expr $node, Scope $scope): ?Type
    {
        /*
         * new Data
         */
        if (
            $node instanceof Expr\New_
            && ($node->class instanceof Node\Name && is_a($node->class->toString(), Data::class, true))
        ) {
            return new Generic($node->class->toString());
        }
        /**
         * Data::collect
         * Data::collection
         * Data::from
         */
        if ($node instanceof Expr\StaticCall) {
            if (! ($node->class instanceof Node\Name && is_a($node->class->toString(), Data::class, true))) {
                return null;
            }

            if (! $node->name instanceof Node\Identifier) {
                return null;
            }

            if (in_array($node->name->toString(), ['collect', 'collection'])) {
                return new Generic(
                    DataCollection::class,
                    [new Generic($node->class->toString())],
                );
            }

            if ($node->name->toString() === 'from') {
                return new Generic($node->class->toString());
            }
        }

        return null;
    }
}
