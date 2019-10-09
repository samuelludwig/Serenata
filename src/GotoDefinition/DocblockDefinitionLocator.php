<?php

namespace Serenata\GotoDefinition;

use UnexpectedValueException;

use Serenata\Analysis\ClasslikeInfoBuilderInterface;
use Serenata\Analysis\DocblockPositionalNameResolverFactory;

use Serenata\Common\FilePosition;
use Serenata\Common\Position;

use Serenata\Utility\Location;
use Serenata\Utility\TextDocumentItem;

final class DocblockDefinitionLocator
{
    /**
     * @var DocblockPositionalNameResolverFactory
     */
    private $docblockPositionalNameResolverFactory;

    /**
     * @var ClasslikeInfoBuilderInterface
     */
    private $classlikeInfoBuilder;

    /**
     * @param DocblockPositionalNameResolverFactory $docblockPositionalNameResolverFactory
     * @param ClasslikeInfoBuilderInterface         $classlikeInfoBuilder
     */
    public function __construct(
        DocblockPositionalNameResolverFactory $docblockPositionalNameResolverFactory,
        ClasslikeInfoBuilderInterface $classlikeInfoBuilder
    ) {
        $this->docblockPositionalNameResolverFactory = $docblockPositionalNameResolverFactory;
        $this->classlikeInfoBuilder = $classlikeInfoBuilder;
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return GotoDefinitionResponse
     */
    public function locate(TextDocumentItem $textDocumentItem, Position $position): GotoDefinitionResponse
    {
        $fqcn = $this->getFqcnFromComment($textDocumentItem, $position);
        $info = $this->classlikeInfoBuilder->build($fqcn);

        return new GotoDefinitionResponse(new Location($info['uri'], $info['range']));
    }

    /**
     * @param TextDocumentItem $textDocumentItem
     * @param Position         $position
     *
     * @return string
     */
    private function getFqcnFromComment(TextDocumentItem $textDocumentItem, Position $position): string
    {
        $line = $this->getLineInText($textDocumentItem->getText(), $position->getLine());

        if (!$this->isCommentLine($line)) {
            throw new UnexpectedValueException('Not a docblock line');
        }

        $word = $this->getWordAtOffset($line, $position->getCharacter());

        if ($word === null || $word === '') {
            throw new UnexpectedValueException(sprintf('No word at %s in line "%s"', $position->getCharacter(), $line));
        }

        $filePosition = new FilePosition($textDocumentItem->getUri(), $position);
        $resolver = $this->docblockPositionalNameResolverFactory->create($filePosition);

        return $resolver->resolve($word, $filePosition);
    }

    /**
     * @param string $text
     * @param int    $line
     *
     * @return string
     */
    private function getLineInText(string $text, int $line): string
    {
        $lines = preg_split('/((\r?\n)|(\r\n?))/', $text);

        if ($lines === false) {
            return $text;
        }

        return $lines[$line];
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    private function isCommentLine(string $line): bool
    {
        return preg_match(
            '/@(?:param|throws|return|var)\s+((?:\\\\?[a-zA-Z_][a-zA-Z0-9_]*(?:\\\\[a-zA-Z_][a-zA-Z0-9_]*)*)(?:\[\])?(?:\|(?:\\\\?[a-zA-Z_][a-zA-Z0-9_]*(?:\\\\[a-zA-Z_][a-zA-Z0-9_]*)*)(?:\[\])?)*)(?:$|\s|\})/',
            $line
        ) === 1;
    }

    /**
     * @param string $line
     * @param int    $offset
     *
     * @return string|null
     */
    private function getWordAtOffset(string $line, int $offset): ?string
    {
        $strings = preg_split('/[\|\s]/', $line, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);

        if ($strings === false) {
            return null;
        }

        foreach ($strings as $string) {
            /** @var int $start */
            $start = $string[1];
            $end = $start + strlen($string[0]);

            if ($offset >= $start && $offset <= $end) {
                return $string[0];
            }
        }

        return null;
    }
}
