<?php

namespace Serenata\GotoDefinition;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use Serenata\Common\Position;

use PhpParser\Node;

use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the function called in {@see Node\Expr\PropertyFetch} nodes.
 */
final class PropertyFetchDefinitionLocator
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
     * @throws DefinitionLocationFailedException
     *
     * @return GotoDefinitionResponse
     */
    public function locate(
        Node\Expr\PropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResponse {
        $infoElements = $this->propertyFetchPropertyInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (count($infoElements) === 0) {
            throw new DefinitionLocationFailedException('No property fetch information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return new GotoDefinitionResponse(new Location($info['uri'], $info['range']));
    }
}
