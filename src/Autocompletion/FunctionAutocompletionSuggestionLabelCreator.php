<?php

namespace Serenata\Autocompletion;

/**
 * Creates labels for autocompletion suggestions for functions.
 */
final class FunctionAutocompletionSuggestionLabelCreator
{
    /**
     * @param array $function
     *
     * @return string
     */
    public function create(array $function): string
    {
        $body = '(';

        $isInOptionalList = false;

        foreach ($function['parameters'] as $index => $param) {
            $description = '';

            if ($param['isOptional'] && !$isInOptionalList) {
                $description .= '[';
            }

            if ($index > 0) {
                $description .= ', ';
            }

            if ($param['isVariadic']) {
                $description .= '...';
            }

            if ($param['isReference']) {
                $description .= '&';
            }

            $description .= '$' . $param['name'];

            if ($param['defaultValue'] !== '' && $param['defaultValue'] !== null) {
                $description .= ' = ' . $param['defaultValue'];
            }

            if ($param['isOptional'] && $index === (count($function['parameters']) - 1)) {
                $description .= ']';
            }

            $isInOptionalList = $param['isOptional'];

            $body .= $description;
        }

        $body .= ')';

        if (isset($function['fqcn'])) {
            return $this->getFqcnWithoutLeadingSlash($function['fqcn']) . $body;
        }

        return $function['name'] . $body;
    }

    /**
     * @param array $classlike
     *
     * @return string
     */
    private function getFqcnWithoutLeadingSlash(string $fqcn): string
    {
        if ($fqcn[0] === '\\') {
            return mb_substr($fqcn, 1);
        }

        return $fqcn;
    }
}
