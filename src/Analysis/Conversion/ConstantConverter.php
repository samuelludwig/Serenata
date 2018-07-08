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
     * @return array
     */
    public function convert(Structures\ConstantLike $constant): array
    {
        $data = [
            'name'              => $constant->getName(),
            // TODO: "+ 1" is only done for backwards compatibility, remove as soon as we can break it.
            'startLine'         => $constant->getRange()->getStart()->getLine() + 1,
            'endLine'           => $constant->getRange()->getEnd()->getLine() + 1,
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

        if ($constant instanceof Structures\Constant) {
            $data['fqcn'] = $constant->getFqcn();
        }

        return $data;
    }
}
