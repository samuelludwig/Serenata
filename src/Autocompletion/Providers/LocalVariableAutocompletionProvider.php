<?php

namespace PhpIntegrator\Autocompletion\Providers;

use UnexpectedValueException;

use PhpParser\Parser;
use PhpParser\ErrorHandler;

use PhpIntegrator\Common\Range;
use PhpIntegrator\Common\Position;

use PhpIntegrator\Utility\TextEdit;
use PhpIntegrator\Utility\SourceCodeHelpers;

use PhpIntegrator\Analysis\VariableScanner;

use PhpIntegrator\Autocompletion\SuggestionKind;
use PhpIntegrator\Autocompletion\AutocompletionSuggestion;
use PhpIntegrator\Autocompletion\AutocompletionSuggestionTypeFormatter;
use PhpIntegrator\Autocompletion\AutocompletionPrefixDeterminerInterface;

use PhpIntegrator\Indexing\Structures\File;

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
     * @var AutocompletionPrefixDeterminerInterface
     */
    private $autocompletionPrefixDeterminer;

    /**
     * @param VariableScanner                         $variableScanner
     * @param Parser                                  $parser
     * @param AutocompletionSuggestionTypeFormatter   $autocompletionSuggestionTypeFormatter
     * @param AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
     */
    public function __construct(
        VariableScanner $variableScanner,
        Parser $parser,
        AutocompletionSuggestionTypeFormatter $autocompletionSuggestionTypeFormatter,
        AutocompletionPrefixDeterminerInterface $autocompletionPrefixDeterminer
    ) {
        $this->variableScanner = $variableScanner;
        $this->parser = $parser;
        $this->autocompletionSuggestionTypeFormatter = $autocompletionSuggestionTypeFormatter;
        $this->autocompletionPrefixDeterminer = $autocompletionPrefixDeterminer;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        $prefix = $this->autocompletionPrefixDeterminer->determine($code, $offset);

        $handler = new ErrorHandler\Collecting();

        try {
            $nodes = $this->parse($code, $handler);
        } catch (UnexpectedValueException $e) {
            return [];
        }

        foreach ($this->variableScanner->getAvailableVariables($nodes, $offset) as $variable) {
            yield $this->createSuggestion($variable, $code, $offset, $prefix);
        }
    }

    /**
     * @param array  $variable
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(
        array $variable,
        string $code,
        int $offset,
        string $prefix
    ): AutocompletionSuggestion {
        $typeArray = array_map(function (string $type) {
            return [
                'fqcn' => $type
            ];
        }, explode('|', $variable['type']));

        return new AutocompletionSuggestion(
            $variable['name'],
            SuggestionKind::VARIABLE,
            $variable['name'],
            $this->getTextEditForSuggestion($variable, $code, $offset, $prefix),
            $variable['name'],
            null,
            [
                'isDeprecated' => false,
                'returnTypes'  => $this->autocompletionSuggestionTypeFormatter->format($typeArray),
                'prefix'       => $prefix
            ]
        );
    }

    /**
     * Generate a {@see TextEdit} for the suggestion.
     *
     * Some clients automatically determine the prefix to replace on their end (e.g. Atom) and just paste the insertText
     * we send back over this prefix. This prefix sometimes differs from what we see as prefix as the namespace
     * separator (the backslash \) whilst these clients don't. Using a {@see TextEdit} rather than a simple insertText
     * ensures that the entire prefix is replaced along with the insertion.
     *
     * @param array  $variable
     * @param string $code
     * @param int    $offset
     * @param string $prefix
     *
     * @return TextEdit
     */
    private function getTextEditForSuggestion(array $variable, string $code, int $offset, string $prefix): TextEdit
    {
        $line = SourceCodeHelpers::calculateLineByOffset($code, $offset) - 1;
        $character = SourceCodeHelpers::getCharacterOnLineFromByteOffset($offset, $line, $code);

        return new TextEdit(
            new Range(new Position($line, $character - mb_strlen($prefix)), new Position($line, $character)),
            $variable['name']
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
