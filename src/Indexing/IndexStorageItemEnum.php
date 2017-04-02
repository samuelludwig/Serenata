<?php

namespace PhpIntegrator\Indexing;

/**
 * Defines item types that are present inside the index.
 */
class IndexStorageItemEnum
{
    /**
     * @var string
     */
    public const FILES                         = 'files';

    /**
     * @var string
     */
    public const STRUCTURE_TYPES               = 'structure_types';

    /**
     * @var string
     */
    public const ACCESS_MODIFIERS              = 'access_modifiers';

    /**
     * @var string
     */
    public const FILES_NAMESPACES              = 'files_namespaces';

    /**
     * @var string
     */
    public const FILES_NAMESPACES_IMPORTS      = 'files_namespaces_imports';

    /**
     * @var string
     */
    public const STRUCTURES                    = 'structures';

    /**
     * @var string
     */
    public const STRUCTURES_PARENTS_LINKED     = 'structures_parents_linked';

    /**
     * @var string
     */
    public const STRUCTURES_INTERFACES_LINKED  = 'structures_interfaces_linked';

    /**
     * @var string
     */
    public const STRUCTURES_TRAITS_LINKED      = 'structures_traits_linked';

    /**
     * @var string
     */
    public const STRUCTURES_TRAITS_ALIASES     = 'structures_traits_aliases';

    /**
     * @var string
     */
    public const STRUCTURES_TRAITS_PRECEDENCES = 'structures_traits_precedences';

    /**
     * @var string
     */
    public const FUNCTIONS                     = 'functions';

    /**
     * @var string
     */
    public const FUNCTIONS_PARAMETERS          = 'functions_parameters';

    /**
     * @var string
     */
    public const PROPERTIES                    = 'properties';

    /**
     * @var string
     */
    public const CONSTANTS                     = 'constants';

    /**
     * @var string
     */
    public const META_STATIC_METHOD_TYPES      = 'meta_static_method_types';
}
