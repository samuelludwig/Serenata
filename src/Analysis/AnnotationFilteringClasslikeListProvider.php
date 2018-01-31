<?php

namespace PhpIntegrator\Analysis;

/**
 * Retrieves a list of classlikes that are annotations.
 */
final class AnnotationFilteringClasslikeListProvider implements ClasslikeListProviderInterface
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
            return $classlike['isAnnotation'] ?? false;
        });
    }
}
