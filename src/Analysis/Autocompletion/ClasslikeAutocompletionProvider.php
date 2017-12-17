<?php

namespace PhpIntegrator\Analysis\Autocompletion;

use PhpIntegrator\Analysis\ClasslikeListProviderInterface;

use PhpIntegrator\Indexing\Structures\File;
use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

/**
 * Provides classlike autocompletion suggestions at a specific location in a file.
 */
final class ClasslikeAutocompletionProvider implements AutocompletionProviderInterface
{
    /**
     * @var ClasslikeListProviderInterface
     */
    private $classlikeListProvider;

    /**
     * @param ClasslikeListProviderInterface $classlikeListProvider
     */
    public function __construct(ClasslikeListProviderInterface $classlikeListProvider)
    {
        $this->classlikeListProvider = $classlikeListProvider;
    }

    /**
     * @inheritDoc
     */
    public function provide(File $file, string $code, int $offset): iterable
    {
        foreach ($this->classlikeListProvider->getAll() as $classlike) {
            yield $this->createSuggestion($classlike);
        }
    }

    /**
     * @param array $classlike
     *
     * @return AutocompletionSuggestion
     */
    private function createSuggestion(array $classlike): AutocompletionSuggestion
    {
        $fqcnWithoutLeadingSlash = $classlike['fqcn'];

        if ($fqcnWithoutLeadingSlash[0] === '\\') {
            $fqcnWithoutLeadingSlash = mb_substr($fqcnWithoutLeadingSlash, 1);
        }

        return new AutocompletionSuggestion(
            $fqcnWithoutLeadingSlash,
            $classlike['type'] === ClasslikeTypeNameValue::TRAIT_ ? SuggestionKind::MIXIN : SuggestionKind::CLASS_,
            $classlike['fqcn'],
            $fqcnWithoutLeadingSlash,
            $classlike['shortDescription'],
            [
                'isDeprecated' => $classlike['isDeprecated'],
                'returnTypes'  => $classlike['type']
            ]
        );
    }
}
