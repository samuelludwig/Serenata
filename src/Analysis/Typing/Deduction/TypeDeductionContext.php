<?php

namespace Serenata\Analysis\Typing\Deduction;

use LogicException;

use PhpParser\Node;

use Serenata\Common\Position;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

/**
 * Represents the context of a type deduction request.
 */
final class TypeDeductionContext
{
    /**
     * @var Node
     */
    private $node;

    /**
     * @var TextDocumentItem
     */
    private $textDocumentItem;

    /**
     * @var Position|null
     */
    private $position;

    /**
     * @param \PhpParser\Node                    $node
     * @param \Serenata\Utility\TextDocumentItem $textDocumentItem
     * @param \Serenata\Common\Position|null     $position
     */
    public function __construct(Node $node, TextDocumentItem $textDocumentItem, ?Position $position = null)
    {
        $this->node = $node;
        $this->textDocumentItem = $textDocumentItem;
        $this->position = $position;
    }

    /**
     * @return Node
     */
    public function getNode(): Node
    {
        return $this->node;
    }

    /**
     * @return \Serenata\Utility\TextDocumentItem
     */
    public function getTextDocumentItem(): \Serenata\Utility\TextDocumentItem
    {
        return $this->textDocumentItem;
    }

    /**
     * @return Position
     */
    public function getPosition(): Position
    {
        if (!$this->position) {
            if (!$this->getNode()->getAttribute('startFilePos')) {
                throw new LogicException('No startFilePos attribute attached to node');
            }

            $this->position = Position::createFromByteOffset(
                $this->getNode()->getAttribute('startFilePos'),
                $this->getTextDocumentItem()->getText(),
                PositionEncoding::VALUE
            );
        }

        return $this->position;
    }
}
