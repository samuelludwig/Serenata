<?php

namespace Serenata\Indexing;

use RuntimeException;

/**
 * Exception that indicates that the index database is of an incorrect version.
 */
final class IncorrectDatabaseVersionException extends RuntimeException
{
}
