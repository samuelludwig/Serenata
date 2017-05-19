<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

use PhpIntegrator\Analysis\Visiting\UseStatementKind;

use PhpIntegrator\Common\Position;
use PhpIntegrator\Common\FilePosition;

use PhpIntegrator\Indexing\IndexDatabase;

use PhpIntegrator\NameQualificationUtilities\PositionalNameLocalizerFactoryInterface;

/**
 * Command that makes a FQCN relative to local use statements in a file.
 */
class LocalizeTypeCommand extends AbstractCommand
{
    /**
     * @var IndexDatabase
     */
    private $indexDatabase;

    /**
     * @var PositionalNameLocalizerFactoryInterface
     */
    private $positionalNameLocalizerFactory;

    /**
     * @param IndexDatabase                           $indexDatabase
     * @param PositionalNameLocalizerFactoryInterface $positionalNameLocalizerFactory
     */
    public function __construct(
        IndexDatabase $indexDatabase,
        PositionalNameLocalizerFactoryInterface $positionalNameLocalizerFactory
    ) {
        $this->indexDatabase = $indexDatabase;
        $this->positionalNameLocalizerFactory = $positionalNameLocalizerFactory;
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

        $type = $this->localizeType(
            $arguments['type'],
            $arguments['file'],
            $arguments['line'],
            isset($arguments['kind']) ? $arguments['kind'] : UseStatementKind::TYPE_CLASSLIKE
        );

        return $type;
    }

    /**
     * Resolves the type.
     *
     * @param string $type
     * @param string $file
     * @param int    $line
     * @param string $kind A constant from {@see UseStatementKind}.
     *
     * @return string|null
     */
    public function localizeType(string $type, string $file, int $line, string $kind): ?string
    {
        $filePosition = new FilePosition($file, new Position($line, 0));

        return $this->positionalNameLocalizerFactory->create($filePosition)->localize($type, $kind);
    }
}
