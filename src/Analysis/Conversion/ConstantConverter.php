<?php

namespace Serenata\Analysis\Conversion;

use Serenata\Indexing\Structures;

/**
 * Converts raw constant data from the index to more useful data.
 */
final class ConstantConverter extends AbstractConverter
{
    /**
     * @param Structures\ConstantLike $constant
     *
     * @return array<string,mixed>
     */
    public function convert(Structures\ConstantLike $constant): array
    {
        $data = [
            'name'              => $constant->getName(),
            'range'             => $constant->getRange(),
            'defaultValue'      => $constant->getDefaultValue(),
            'uri'               => $constant->getFile()->getUri(),

            'isStatic'          => true,
            'isDeprecated'      => $constant->getIsDeprecated(),
            'hasDocblock'       => $constant->getHasDocblock(),
            'hasDocumentation'  => $constant->getHasDocblock(),

            'shortDescription'  => $constant->getShortDescription(),
            'longDescription'   => $constant->getLongDescription(),
            'typeDescription'   => $constant->getTypeDescription(),

            'types'             => $this->convertDocblockType($constant->getType()),
        ];

        if ($constant instanceof Structures\Constant) {
            $data['fqcn'] = $constant->getFqcn();
        }

        return $data;
    }
}
