<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use GetOptionKit\OptionCollection;

use PhpIntegrator\Analysis\Typing\ProjectTypeResolverFactoryFacade;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Indexing\IndexDatabase;

/**
 * Command that resolves local types in a file.
 */
class ResolveTypeCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    protected $indexDatabase;

    /**
     * @var ProjectTypeResolverFactoryFacade
     */
    protected $projectTypeResolverFactoryFacade;

    /**
     * @param IndexDatabase                    $indexDatabase
     * @param ProjectTypeResolverFactoryFacade $projectTypeResolverFactoryFacade
     */
    public function __construct(
        IndexDatabase $indexDatabase,
        ProjectTypeResolverFactoryFacade $projectTypeResolverFactoryFacade
    ) {
        $this->indexDatabase = $indexDatabase;
        $this->projectTypeResolverFactoryFacade = $projectTypeResolverFactoryFacade;
    }

    /**
     * @inheritDoc
     */
    public function attachOptions(OptionCollection $optionCollection)
    {
        $optionCollection->add('kind?', 'What you want to resolve. Either "classlike" (the default), "function" or "constant".')->isa('string');
        $optionCollection->add('line:', 'The line on which the type can be found, line 1 being the first line.')->isa('number');
        $optionCollection->add('type:', 'The name of the type to resolve.')->isa('string');
        $optionCollection->add('file:', 'The file in which the type needs to be resolved.')->isa('string');
    }

    /**
     * @inheritDoc
     */
    public function execute(ArrayAccess $arguments)
    {
        if (!isset($arguments['type'])) {
            throw new InvalidArgumentsException('The type is required for this command.');
        } elseif (!isset($arguments['file'])) {
            throw new InvalidArgumentsException('A file name is required for this command.');
        } elseif (!isset($arguments['line'])) {
            throw new InvalidArgumentsException('A line number is required for this command.');
        }

        $type = $this->resolveType(
            $arguments['type']->value,
            $arguments['file']->value,
            $arguments['line']->value,
            isset($arguments['kind']->value) ? $arguments['kind']->value : UseStatementKind::TYPE_CLASSLIKE
        );

        return $type;
    }

    /**
     * Resolves the type.
     *
     * @param string $name
     * @param string $file
     * @param int    $line
     * @param string $kind A constant from {@see UseStatementKind}.
     *
     * @throws InvalidArgumentsException
     *
     * @return string|null
     */
    public function resolveType($name, $file, $line, $kind)
    {
        $recognizedKinds = [
            UseStatementKind::TYPE_CLASSLIKE,
            UseStatementKind::TYPE_FUNCTION,
            UseStatementKind::TYPE_CONSTANT
        ];

        if (!in_array($kind, $recognizedKinds)) {
            throw new InvalidArgumentsException('Unknown kind specified!');
        }

        $fileId = $this->indexDatabase->getFileId($file);

        if (!$fileId) {
            throw new InvalidArgumentsException('The specified file is not present in the index!');
        }

        return $this->projectTypeResolverFactoryFacade->create($file)->resolve($name, $line, $kind);
    }
}
