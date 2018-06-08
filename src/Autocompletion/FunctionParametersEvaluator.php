<?php declare(strict_types=1);

namespace Serenata\Autocompletion;

class FunctionParametersEvaluator
{
    public function hasRequiredParameters(array $function): bool
    {
        // foreach can handle empty arrays.
        foreach ($function['parameters'] as $parameter) {
            if (!array_key_exists('isOptional', $parameter) || !$parameter['isOptional']) {
                return true;
            }
        }

        return false;
    }
}
