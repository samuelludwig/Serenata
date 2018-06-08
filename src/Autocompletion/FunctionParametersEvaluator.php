<?php declare(strict_types=1);

namespace Serenata\Autocompletion;

/**
 * Evaluates function parameters and allows additional queries on them.
 */
class FunctionParametersEvaluator
{
    /**
     * @param array $function
     *
     * @return bool
     */
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
