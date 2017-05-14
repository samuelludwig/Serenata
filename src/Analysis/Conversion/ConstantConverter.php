<?php

namespace PhpIntegrator\Analysis\Conversion;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw constant data from the index to more useful data.
 */
class ConstantConverter extends AbstractConverter
{
    /**
     * @param Structures\Constant $constant
     *
     * @return array
     */
    public function convert(Structures\Constant $constant): array
    {
        return [
            'name'              => $constant->getName(),
            'fqcn'              => $constant->getFqcn(),
            'isBuiltin'         => $constant->getIsBuiltin(),
            'startLine'         => $constant->getStartLine(),
            'endLine'           => $constant->getEndLine(),
            'defaultValue'      => $constant->getDefaultValue(),
            'filename'          => $constant->getFilename(),

            'isPublic'          => $constant->getAccessModifier() ? $constant->getAccessModifier()->getName() === 'public' : true,
            'isProtected'       => $constant->getAccessModifier() ? $constant->getAccessModifier()->getName() === 'protected' : false,
            'isPrivate'         => $constant->getAccessModifier() ? $constant->getAccessModifier()->getName() === 'private' : false,
            'isStatic'          => $constant->getIsStatic(),
            'isDeprecated'      => $constant->getIsDeprecated(),
            'hasDocblock'       => $constant->getHasDocblock(),
            'hasDocumentation'  => $constant->getHasDocumentation(),

            'shortDescription'  => $constant->getShortDescription(),
            'longDescription'   => $constant->getLongDescription(),
            'typeDescription'   => $constant->getTypeDescription(),

            'types'             => $this->convertTypes($constant->getTypes())
        ];
    }
}
