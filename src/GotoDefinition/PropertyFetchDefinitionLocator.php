<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use Serenata\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use Serenata\Indexing\Structures;

use PhpParser\Node;

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
     * @param Structures\File         $file
     * @param string                  $code
     * @param int                     $offset
     *
     * @throws UnexpectedValueException
     *
     * @return GotoDefinitionResult
     */
    public function locate(
        Node\Expr\PropertyFetch $node,
        Structures\File $file,
        string $code,
        int $offset
    ): GotoDefinitionResult {
        $infoElements = $this->propertyFetchPropertyInfoRetriever->retrieve($node, $file, $code, $offset);

        if (empty($infoElements)) {
            throw new UnexpectedValueException('No property fetch information was found for node');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        $info = array_shift($infoElements);

        return new GotoDefinitionResult($info['filename'], $info['startLine']);
    }
}
