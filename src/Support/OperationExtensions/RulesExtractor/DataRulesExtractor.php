<?php

namespace Dedoc\Scramble\Support\OperationExtensions\RulesExtractor;

use PhpParser\Node\Param;
use Spatie\LaravelData\Data;
use PhpParser\Node\FunctionLike;

class DataRulesExtractor
{
    private ?FunctionLike $handler;

    public function __construct(?FunctionLike $handler)
    {
        $this->handler = $handler;
    }

    public function shouldHandle()
    {
        if (! $this->handler) {
            return false;
        }

        return collect($this->handler->getParams())
            ->contains(\Closure::fromCallable([$this, 'findCustomRequestParam']));
    }

    public function extract()
    {
        /** @var Data $requestClassName */
        $requestClassName = $this->getFormRequestClassName();

        return $requestClassName::getValidationRules([]);
    }

    private function getFormRequestClassName()
    {
        $requestParam = collect($this->handler->getParams())
            ->first(\Closure::fromCallable([$this, 'findCustomRequestParam']));

        return (string) $requestParam->type;
    }

    private function findCustomRequestParam(Param $param)
    {
        $className = (string) $param->type;

        return is_a($className, Data::class, true);
    }
}
