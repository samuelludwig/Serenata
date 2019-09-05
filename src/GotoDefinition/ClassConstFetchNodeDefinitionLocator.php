<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use PhpParser\Node;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;

use Serenata\Analysis\Typing\Deduction\TypeDeductionContext;
use Serenata\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use Serenata\Common\Position;


use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

/**
 * Locates the definition of the class constant called in {@see Node\Expr\ClassConstFetch} nodes.
 */
final class ClassConstFetchNodeDefinitionLocator
{
    /**
     * @var NodeTypeDeducerInterface
     */
    private $nodeTypeDeducer;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param NodeTypeDeducerInterface      $nodeTypeDeducer
     * @param ClasslikeInfoBuilderInterface $classlikeInfoBuilder
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->nodeTypeDeducer = $nodeTypeDeducer;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws UnexpectedValueException when the constant name is not a string (i.e. an error node).
     * @throws UnexpectedValueException when the type of the class could not be determined.
     * @throws UnexpectedValueException when no tooltips could be determined.
     *
     * @return GotoDefinitionResponse
     */
    public function locate(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): GotoDefinitionResponse {
        if (!$node->name instanceof Node\Identifier) {
            throw new UnexpectedValueException("Can't deduce the type of a non-string node");
        }

        $classTypes = $this->getClassTypes($node, $textDocumentItem, $position);

        $definitions = [];

        foreach ($classTypes as $classType) {
            $constantInfo = $this->fetchClassConstantInfo($classType, $node->name);

            if ($constantInfo === null) {
                continue;
            }

            $definitions[] = new GotoDefinitionResponse(new Location($constantInfo['uri'], $constantInfo['range']));
        }

        if (count($definitions) === 0) {
            throw new UnexpectedValueException('Could not determine any definition for the class constant');
        }

        // Fetch the first tooltip. In theory, multiple tooltips are possible, but we don't support these at the moment.
        return $definitions[0];
    }

    /**
     * @param Node\Expr\ClassConstFetch $node
     * @param TextDocumentItem          $textDocumentItem
     * @param Position                  $position
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    private function getClassTypes(
        Node\Expr\ClassConstFetch $node,
        TextDocumentItem $textDocumentItem,
        Position $position
    ): array {
        $classTypes = [];

        try {
            $classTypes = $this->nodeTypeDeducer->deduce(new TypeDeductionContext(
                $node->class,
                $textDocumentItem,
                $position
            ));
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException('Could not deduce the type of class', 0, $e);
        }

        if (count($classTypes) === 0) {
            throw new UnexpectedValueException('No types returned for class');
        }

        return $classTypes;
    }

    /**
     * @param string $classType
     * @param string $name
     *
     * @return array|null
     */
    private function fetchClassConstantInfo(string $classType, string $name): ?array
    {
        $classInfo = null;

        try {
            $classInfo = $this->classlikeInfoBuilder->build($classType);
        } catch (UnexpectedValueException $e) {
            return null;
        }

        if (!isset($classInfo['constants'][$name])) {
            return null;
        }

        return $classInfo['constants'][$name];
    }
}
