<?php

namespace Serenata\Refactoring;

/**
 * Indicates a use statement cannot be added for a non-compound name (e.g. "DateTime") in an (implicitly or explicitly)
 * anonymous namespace.
 *
 * PHP generates a warning about these.
 */
final class NonCompoundNameInAnonymousNamespaceException extends UseStatementInsertionCreationException
{
}
