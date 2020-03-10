<?php

namespace Serenata\Analysis\Conversion;

use Serenata\Indexing\Structures;

/**
 * Converts raw namespace data from the index to more useful data.
 */
final class NamespaceConverter extends AbstractConverter
{
    /**
     * @param Structures\FileNamespace $namespace
     *
     * @return array<string,mixed>
     */
    public function convert(Structures\FileNamespace $namespace): array
    {
        return [
            'id'    => $namespace->getId(),
            'name'  => $namespace->getName(),
            'uri'   => $namespace->getFile()->getUri(),
            'range' => $namespace->getRange(),
        ];
    }
}
