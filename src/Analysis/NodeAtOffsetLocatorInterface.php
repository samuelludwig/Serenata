<?php

namespace Serenata\Analysis;

use Serenata\Common\Position;

use Serenata\Utility\TextDocumentItem;

/**
 * Interface for classes that locate the node at the specified offset in code.
 */
interface NodeAtOffsetLocatorInterface
{
    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return NodeAtOffsetLocatorResult
     */
    public function locate(TextDocumentItem $textDocumentItem, Position $position): NodeAtOffsetLocatorResult;
}
