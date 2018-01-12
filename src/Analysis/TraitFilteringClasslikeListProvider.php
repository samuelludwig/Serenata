<?php

namespace PhpIntegrator\Analysis;

use PhpIntegrator\Indexing\Structures\ClasslikeTypeNameValue;

/**
 * Retrieves a list of classlikes that are traits.
 */
final class TraitFilteringClasslikeListProvider implements ClasslikeListProviderInterface
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
            return $classlike['type'] === ClasslikeTypeNameValue::TRAIT_;
        });
    }
}
