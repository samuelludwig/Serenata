<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Autocompletion\AutocompletionProviderInterface;

use PhpIntegrator\Utility\SourceCodeHelpers;
use PhpIntegrator\Utility\SourceCodeStreamReader;

/**
 * Command that shows autocompletion suggestions at a specific location.
 */
class AutocompleteCommand extends AbstractCommand
{
    /**
     * @var AutocompletionProviderInterface
     */
    private $autocompletionProvider;

    /**
     * @var sourceCodeStreamReader
     */
    private $sourceCodeStreamReader;

    /**
     * @param AutocompletionProviderInterface $autocompletionProvider
     * @param sourceCodeStreamReader          $sourceCodeStreamReader
     */
    public function __construct(
        AutocompletionProviderInterface $autocompletionProvider,
        sourceCodeStreamReader $sourceCodeStreamReader
    ) {
        $this->autocompletionProvider = $autocompletionProvider;
        $this->sourceCodeStreamReader = $sourceCodeStreamReader;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        if (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A --file must be supplied!');
        } elseif (!isset($arguments['offset'])) {
            throw new InvalidArgumentsException('An --offset must be supplied into the source code!');
        }

        $code = null;

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } else {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] == true) {
            $offset = SourceCodeHelpers::getByteOffsetFromCharacterOffset($offset, $code);
        }

        return $this->getAutocompletionSuggestions($code, $offset);
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @return array
     */
    public function getAutocompletionSuggestions(string $code, int $offset): array
    {
        return iterator_to_array($this->autocompletionProvider->provide($code, $offset), false);
    }
}
