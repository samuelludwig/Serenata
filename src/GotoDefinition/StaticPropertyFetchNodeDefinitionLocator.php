<?php

namespace Serenata\GotoDefinition;

use PhpParser\Node;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use Serenata\Common\Position;

use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the function called in {@see Node\Expr\StaticPropertyFetch} nodes.
 */
final class StaticPropertyFetchNodeDefinitionLocator
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
     * @param Node\Expr\StaticPropertyFetch $node
     * @param TextDocumentItem              $textDocumentItem
     * @param Position                      $position
     *
     * @throws DefinitionLocationFailedException
     *
     * @return GotoDefinitionResponse
     */
    public function locate(
        Node\Expr\StaticPropertyFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResponse {
        $infoElements = $this->propertyFetchPropertyInfoRetriever->retrieve($node, $textDocumentItem, $position);

        if (count($infoElements) === 0) {
            throw new DefinitionLocationFailedException('No property fetch information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        assert(
            $info !== null,
            'Null should never be returned on shifting as the error was already guaranteed to be not empty'
        );

        return new GotoDefinitionResponse(new Location($info['uri'], $info['range']));
    }
}
