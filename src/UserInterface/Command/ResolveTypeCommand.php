<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;
use UnexpectedValueException;

use GetOptionKit\OptionCollection;

use PhpIntegrator\Analysis\Typing\FileTypeResolverFactory;

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
     * @var FileTypeResolverFactory
     */
    protected $fileTypeResolverFactory;

    /**
     * @param IndexDatabase           $indexDatabase
     * @param FileTypeResolverFactory $fileTypeResolverFactory
     */
    public function __construct(IndexDatabase $indexDatabase, FileTypeResolverFactory $fileTypeResolverFactory)
    {
        $this->indexDatabase = $indexDatabase;
        $this->fileTypeResolverFactory = $fileTypeResolverFactory;
    }

    /**
     * @inheritDoc
     */
    public function attachOptions(OptionCollection $optionCollection)
    {
        $optionCollection->add('kind?', 'What you want to resolve. Either "classlike" (the default), "function" or "constant".')->isa('string');
        $optionCollection->add('line:', 'The line on which the type can be found, line 1 being the first line.')->isa('number');
        $optionCollection->add('type:', 'The name of the type to resolve.')->isa('string');
        $optionCollection->add('file:', 'The file in which the type needs to be resolved..')->isa('string');
    }

    /**
     * @inheritDoc
     */
    protected function process(ArrayAccess $arguments)
    {
        if (!isset($arguments['type'])) {
            throw new UnexpectedValueException('The type is required for this command.');
        } elseif (!isset($arguments['file'])) {
            throw new UnexpectedValueException('A file name is required for this command.');
        } elseif (!isset($arguments['line'])) {
            throw new UnexpectedValueException('A line number is required for this command.');
        }

        $type = $this->resolveType(
            $arguments['type']->value,
            $arguments['file']->value,
            $arguments['line']->value,
            isset($arguments['kind']->value) ? $arguments['kind']->value : UseStatementKind::TYPE_CLASSLIKE
        );

        return $this->outputJson(true, $type);
    }

    /**
     * Resolves the type.
     *
     * @param string $type
     * @param string $file
     * @param int    $line
     * @param string $kind A constant from {@see UseStatementKind}.
     *
     * @throws UnexpectedValueException
     *
     * @return string|null
     */
    public function resolveType($type, $file, $line, $kind)
    {
        $recognizedKinds = [
            UseStatementKind::TYPE_CLASSLIKE,
            UseStatementKind::TYPE_FUNCTION,
            UseStatementKind::TYPE_CONSTANT
        ];

        if (!in_array($kind, $recognizedKinds)) {
            throw new UnexpectedValueException('Unknown kind specified!');
        }

        $fileId = $this->indexDatabase->getFileId($file);

        if (!$fileId) {
            throw new UnexpectedValueException('The specified file is not present in the index!');
        }

        return $this->fileTypeResolverFactory->create($file)->resolve($type, $line, $kind);
    }
}
