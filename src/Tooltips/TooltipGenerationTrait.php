<?php

namespace PhpIntegrator\Tooltips;

/**
 * Trait for tooltip generators.
 */
trait TooltipGenerationTrait
{
    /**
     * @param array $typeArray
     *
     * @return string
     */
    protected function getTypeStringForTypeArray(array $typeArray): string
    {
        if (empty($typeArray)) {
            return '(Not known)';
        }

        $typeList = [];

        foreach ($typeArray as $type) {
            $typeList[] = $type['type'];
        }

        return implode('|', $typeList);
    }
}
