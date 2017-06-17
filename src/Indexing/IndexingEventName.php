<?php

namespace PhpIntegrator\Indexing;

/**
 * Enumeration of indexing event names.
 */
class IndexingEventName
{
    /**
     * @var string
     */
    public const EVENT_NAMESPACE_UPDATED = 'namespaceUpdated';

    /**
     * @var string
     */
    public const EVENT_NAMESPACE_REMOVED = 'namespaceRemoved';

    /**
     * @var string
     */
    public const EVENT_IMPORT_INSERTED = 'importInserted';

    /**
     * @var string
     */
    public const EVENT_CONSTANT_UPDATED = 'constantUpdated';

    /**
     * @var string
     */
    public const EVENT_CONSTANT_REMOVED = 'constantRemoved';

    /**
     * @var string
     */
    public const EVENT_FUNCTION_UPDATED = 'functionUpdated';

    /**
     * @var string
     */
    public const EVENT_FUNCTION_REMOVED = 'functionRemoved';

    /**
     * @var string
     */
    public const EVENT_STRUCTURE_UPDATED = 'structureUpdated';

    /**
     * @var string
     */
    public const EVENT_STRUCTURE_REMOVED = 'structureRemoved';
}
