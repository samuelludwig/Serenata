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
     * @var array|null
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
     * @param array $namespace
     */
    public function add(array $namespace): void
    {
        $this->initializeRegistryIfNecessary();

        $this->registry[$namespace['id']] = $namespace;
    }

    /**
     * @param array $namespace
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
     * @return array
     */
    private function getRegistry(): array
    {
        $this->initializeRegistryIfNecessary();

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
