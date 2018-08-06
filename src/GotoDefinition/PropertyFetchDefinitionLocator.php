<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use PhpParser\Node;

use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the function called in {@see Node\Expr\PropertyFetch} nodes.
 */
class PropertyFetchDefinitionLocator
{
    /**
     * @var PropertyFetchPropertyInfoRetriever
     */
    private $propertyFetchPropertyInfoRetriever;

    /**
     * @param PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever
     */
    public function __construct(PropertyFetchPropertyInfoRetriever $propertyFetchPropertyInfoRetriever)
    {
        $this->propertyFetchPropertyInfoRetriever = $propertyFetchPropertyInfoRetriever;
    }

    /**
     * @param Node\Expr\PropertyFetch $node
     * @param TextDocumentItem        $textDocumentItem
     * @param Position                $position
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    public function locate(
        Node\Expr\PropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResult {
        $infoElements = $this->propertyFetchPropertyInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (empty($infoElements)) {
            throw new UnexpectedValueException('No property fetch information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
    }
}
