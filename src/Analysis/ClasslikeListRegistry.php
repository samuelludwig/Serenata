<?php

namespace Serenata\Analysis;

/**
 * Registry that maintains a list of classlikes.
 */
final class ClasslikeListRegistry implements ClasslikeListProviderInterface
{
    /**
     * @var ClasslikeListProviderInterface
     */
    private $delegate;

    /**
     * @var array|null
     */
    private $registry;

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
        return $this->getRegistry();
    }

    /**
     * @param array $classlike
     */
    public function add(array $classlike): void
    {
        $this->initializeRegistryIfNecessary();

        $this->registry[$classlike['fqcn']] = $classlike;
    }

    /**
     * @param array $classlike
     */
    public function remove(array $classlike): void
    {
        $this->initializeRegistryIfNecessary();

        if (isset($this->registry[$classlike['fqcn']])) {
            unset($this->registry[$classlike['fqcn']]);
        }
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->registry = null;
    }

    /**
     * @return array
     */
    private function getRegistry(): array
    {
        $this->initializeRegistryIfNecessary();

        assert($this->registry !== null);

        return $this->registry;
    }

    /**
     * @return void
     */
    private function initializeRegistryIfNecessary(): void
    {
        if ($this->registry === null) {
            $this->initializeRegistry();
        }
    }

    /**
     * @return void
     */
    private function initializeRegistry(): void
    {
        $this->registry = $this->delegate->getAll();
    }
}
