<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use UnexpectedValueException;

use PhpIntegrator\Analysis\VariableScanner;

use PhpIntegrator\Indexing\Structures\File;

use PhpParser\Parser;
use PhpParser\ErrorHandler;

/**
 * Provides local variable autocompletion suggestions at a specific location in a file.
 */
final class LocalVariableAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var VariableScanner
     */
    private $variableScanner;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param VariableScanner $variableScanner
     * @param Parser          $parser
     */
    public function __construct(VariableScanner $variableScanner, Parser $parser)
    {
        $this->variableScanner = $variableScanner;
        $this->parser = $parser;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $handler = new ErrorHandler\Collecting();

        try {
            $nodes = $this->parse($code, $handler);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        foreach ($this->variableScanner->getAvailableVariables($nodes, $offset) as $variable) {
            yield $this->createSuggestion($variable);
        }
    }

    /**
     * @param array $variable
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $variable): AutocompletionSuggestion
    {
        return new AutocompletionSuggestion(
            $variable['name'],
            SuggestionKind::VARIABLE,
            $variable['name'],
            null,
            $variable['name'],
            null,
            [
                'isDeprecated' => false,
                'returnTypes'  => $this->createReturnTypes($variable)
            ]
        );
    }

    /**
     * @param array $variable
     *
     * @return string
     */
    private function createReturnTypes(array $variable): string
    {
        $typeNames = $this->getShortReturnTypes($variable);

        return implode('|', $typeNames);
    }

    /**
     * @param array $variable
     *
     * @return string[]
     */
    private function getShortReturnTypes(array $variable): array
    {
        $shortTypes = [];

        foreach (explode('|', $variable['type']) as $type) {
            $shortTypes[] = $this->getClassShortNameFromFqcn($type);
        }

        return $shortTypes;
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    private function getClassShortNameFromFqcn(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return array_pop($parts);
    }

    /**
     * @param string            $code
     * @param ErrorHandler|null $errorHandler
     *
     * @throws UnexpectedValueException
     *
     * @return \PhpParser\Node[]
     */
    private function parse(string $code, ?ErrorHandler $errorHandler = null): array
    {
        try {
            $nodes = $this->parser->parse($code, $errorHandler);
        } catch (\PhpParser\Error $e) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        if ($nodes === null) {
            throw new UnexpectedValueException('Parsing the file failed!');
        }

        return $nodes;
    }
}
