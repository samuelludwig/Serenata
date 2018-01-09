<?php

namespace PhpIntegrator\Autocompletion;

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
     * @var AutocompletionSuggestionTypeFormatter
     */
    private $autocompletionSuggestionTypeFormatter;

    /**
     * @param VariableScanner                       $variableScanner
     * @param Parser                                $parser
     * @param AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter
     */
    public function __construct(
        VariableScanner $variableScanner,
        Parser $parser,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter
    ) {
        $this->variableScanner = $variableScanner;
        $this->parser = $parser;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
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
        $typeArray = array_map(function (string $type) {
            return [
                'fqcn' => $type
            ];
        }, explode('|', $variable['type']));

        return new AutocompletionSuggestion(
            $variable['name'],
            SuggestionKind::VARIABLE,
            $variable['name'],
            null,
            $variable['name'],
            null,
            [
                'isDeprecated' => false,
                'returnTypes'  => $this->autocompletionSuggestionTypeFormatter->format($typeArray)
            ]
        );
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
