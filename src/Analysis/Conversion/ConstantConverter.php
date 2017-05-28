<?php

namespace PhpIntegrator\Analysis\Conversion;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw constant data from the index to more useful data.
 */
class ConstantConverter extends AbstractConverter
{
    /**
     * @param Structures\ConstantLike $constant
     *
     * @return array
     */
    public function convert(Structures\ConstantLike $constant): array
    {
        return [
            'name'              => $constant->getName(),
            'fqcn'              => $constant->getFqcn(),
            'startLine'         => $constant->getStartLine(),
            'endLine'           => $constant->getEndLine(),
            'defaultValue'      => $constant->getDefaultValue(),
            'filename'          => $constant->getFile()->getPath(),

            'isStatic'          => true,
            'isDeprecated'      => $constant->getIsDeprecated(),
            'hasDocblock'       => $constant->getHasDocblock(),
            'hasDocumentation'  => $constant->getHasDocblock(),

            'shortDescription'  => $constant->getShortDescription(),
            'longDescription'   => $constant->getLongDescription(),
            'typeDescription'   => $constant->getTypeDescription(),

            'types'             => $this->convertTypes($constant->getTypes())
        ];
    }
}
