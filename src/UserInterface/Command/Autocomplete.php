<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Autocompletion\AutocompletionProvider;

use PhpIntegrator\Utility\SourceCodeHelpers;

/**
 * Command that shows autocompletion suggestions at a specific location.
 */
class Autocomplete extends AbstractCommand
{
    /**
     * @var AutocompletionProvider
     */
    protected $autocompletionProvider;

    /**
     * @param AutocompletionProvider $autocompletionProvider
     */
    public function __construct(AutocompletionProvider $autocompletionProvider)
    {
        $this->autocompletionProvider = $autocompletionProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        if (!isset($arguments['offset'])) {
            throw new InvalidArgumentsException('An --offset must be supplied into the source code!');
        }

        $code = null;

        if (isset($arguments['stdin']) && $arguments['stdin']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromStdin();
        } elseif (isset($arguments['file']) && $arguments['file']) {
            $code = $this->sourceCodeStreamReader->getSourceCodeFromFile($arguments['file']);
        } else {
            throw new InvalidArgumentsException('Either a --file file must be supplied or --stdin must be passed!');
        }

        $offset = $arguments['offset'];

        if (isset($arguments['charoffset']) && $arguments['charoffset'] == true) {
            $offset = SourceCodeHelpers::getByteOffsetFromCharacterOffset($offset, $code);
        }

        $result = $this->getAutocompletionSuggestions($code, $offset);

        return $result;
    }

    /**
     * @param string $code
     * @param int    $offset
     *
     * @return array
     */
    public function getAutocompletionSuggestions(string $code, int $offset): array
    {
        return $this->autocompletionProvider->getSuggestions($code, $offset);
    }
}
