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
     * @return array<string,mixed>
     */
    public function convert(Structures\Classlike $classlike): array
    {
        $data = [
            'name'               => $classlike->getName(),
            'fqcn'               => $classlike->getFqcn(),
            'range'              => $classlike->getRange(),
            'uri'                => $classlike->getFile()->getUri(),
            'type'               => $classlike->getTypeName(),
            'isDeprecated'       => $classlike->getIsDeprecated(),
            'hasDocblock'        => $classlike->getHasDocblock(),
            'hasDocumentation'   => $classlike->getHasDocblock(),
            'shortDescription'   => $classlike->getShortDescription(),
            'longDescription'    => $classlike->getLongDescription(),
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
