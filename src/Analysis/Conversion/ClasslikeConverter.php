<?php

namespace Serenata\Analysis\Conversion;

use Serenata\Indexing\Structures;

/**
 * Converts raw classlike data from the index to more useful data.
 */
final class ClasslikeConverter extends AbstractConverter
{
    /**
     * @param Structures\Classlike $classlike
     *
     * @return array
     */
    public function convert(Structures\Classlike $classlike): array
    {
        $data = [
            'name'               => $classlike->getName(),
            'fqcn'               => $classlike->getFqcn(),
            // TODO: "+ 1" is only done for backwards compatibility, remove as soon as we can break it.
            'startLine'          => $classlike->getRange()->getStart()->getLine() + 1,
            'endLine'            => $classlike->getRange()->getEnd()->getLine() + 1,
            'filename'           => $classlike->getFile()->getPath(),
            'type'               => $classlike->getTypeName(),
            'isDeprecated'       => $classlike->getIsDeprecated(),
            'hasDocblock'        => $classlike->getHasDocblock(),
            'hasDocumentation'   => $classlike->getHasDocblock(),
            'shortDescription'   => $classlike->getShortDescription(),
            'longDescription'    => $classlike->getLongDescription()
        ];

        if ($classlike instanceof Structures\Class_) {
            $data['isAnonymous']  = $classlike->getIsAnonymous();
            $data['isAbstract']   = $classlike->getIsAbstract();
            $data['isFinal']      = $classlike->getIsFinal();
            $data['isAnnotation'] = $classlike->getIsAnnotation();
        }

        return $data;
    }
}
