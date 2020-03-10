<?php

namespace Serenata\Analysis;

/**
 * Registry that maintains a list of namespaces.
 */
final class NamespaceListRegistry implements NamespaceListProviderInterface
{
    /**
     * @var NamespaceListProviderInterface
     */
    private $delegate;

    /**
     * @var array<string,array<string,mixed>>|null
     */
    private $registry;

    /**
     * @param NamespaceListProviderInterface $delegate
     */
    public function __construct(NamespaceListProviderInterface $delegate)
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
     * @param array<string,mixed> $namespace
     */
    public function add(array $namespace): void
    {
        $this->initializeRegistryIfNecessary();

        $this->registry[$namespace['id']] = $namespace;
    }

    /**
     * @param array<string,mixed> $namespace
     */
    public function remove(array $namespace): void
    {
        $this->initializeRegistryIfNecessary();

        if (isset($this->registry[$namespace['id']])) {
            unset($this->registry[$namespace['id']]);
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
     * @return array<string,array<string,mixed>>
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
