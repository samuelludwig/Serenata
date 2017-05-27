<?php

namespace PhpIntegrator\Analysis\Conversion;

use PhpIntegrator\Indexing\Structures;

/**
 * Converts raw classlike data from the index to more useful data.
 */
class ClasslikeConverter extends AbstractConverter
{
    /**
     * @param Structures\Structure $structure
     *
     * @return array
     */
    public function convert(Structures\Structure $structure): array
    {
        return [
            'name'               => $structure->getName(),
            'fqcn'               => $structure->getFqcn(),
            'startLine'          => $structure->getStartLine(),
            'endLine'            => $structure->getEndLine(),
            'filename'           => $structure->getFile()->getPath(),
            'type'               => $structure->getType()->getName(),
            'isAbstract'         => $structure->getIsAbstract(),
            'isFinal'            => $structure->getIsFinal(),
            'isDeprecated'       => $structure->getIsDeprecated(),
            'isAnnotation'       => $structure->getIsAnnotation(),
            'hasDocblock'        => $structure->getHasDocblock(),
            'hasDocumentation'   => $structure->getHasDocblock(),
            'shortDescription'   => $structure->getShortDescription(),
            'longDescription'    => $structure->getLongDescription()
        ];
    }
}
