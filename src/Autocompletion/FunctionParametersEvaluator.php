<?php declare(strict_types=1);

/**
 * @project serenata
 */

namespace Serenata\Autocompletion;

class FunctionParametersEvaluator
{
    public function hasRequiredParameters(array $function): bool
    {
        if (array_key_exists('parameters', $function) && is_array($function['parameters']) && count($function['parameters']) > 0) {
            foreach ($function['parameters'] as $parameter) {
                if (!array_key_exists('isOptional', $parameter) || !$parameter['isOptional']) {
                    return true;
                }
            }
        }

        return false;
    }
}
