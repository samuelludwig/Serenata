<?php

namespace Serenata\Analysis;

use Serenata\Indexing\Structures\ClasslikeTypeNameValue;

/**
 * Retrieves a list of classlikes that are classes.
 */
final class ClassFilteringClasslikeListProvider implements ClasslikeListProviderInterface
{
    /**
     * @var ClasslikeListProviderInterface
     */
    private $delegate;

    /**
     * @param ClasslikeListProviderInterface $delegate
     */
    public function __construct(ClasslikeListProviderInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return array_filter($this->delegate->getAll(), function (array $classlike) {
            return $classlike['type'] === ClasslikeTypeNameValue::CLASS_;
        });
    }
}
