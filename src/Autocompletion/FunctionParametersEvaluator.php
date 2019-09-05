<?php declare(strict_types=1);

namespace Serenata\Autocompletion;

/**
 * Evaluates function parameters and allows additional queries on them.
 */
final class FunctionParametersEvaluator
{
    /**
     * @param array $function
     *
     * @return bool
     */
    public function hasRequiredParameters(array $function): bool
    {
        foreach ($function['parameters'] as $parameter) {
            if (!$parameter['isOptional']) {
                return true;
            }
        }

        return false;
    }
}
