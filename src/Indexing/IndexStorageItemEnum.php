<?php

namespace PhpIntegrator\Indexing;

/**
 * Defines item types that are present inside the index.
 */
class IndexStorageItemEnum
{
    public const FILES                         = 'files';
    public const STRUCTURE_TYPES               = 'structure_types';
    public const ACCESS_MODIFIERS              = 'access_modifiers';
    public const FILES_NAMESPACES              = 'files_namespaces';
    public const FILES_NAMESPACES_IMPORTS      = 'files_namespaces_imports';
    public const STRUCTURES                    = 'structures';
    public const STRUCTURES_PARENTS_LINKED     = 'structures_parents_linked';
    public const STRUCTURES_INTERFACES_LINKED  = 'structures_interfaces_linked';
    public const STRUCTURES_TRAITS_LINKED      = 'structures_traits_linked';
    public const STRUCTURES_TRAITS_ALIASES     = 'structures_traits_aliases';
    public const STRUCTURES_TRAITS_PRECEDENCES = 'structures_traits_precedences';
    public const FUNCTIONS                     = 'functions';
    public const FUNCTIONS_PARAMETERS          = 'functions_parameters';
    public const PROPERTIES                    = 'properties';
    public const CONSTANTS                     = 'constants';
}
