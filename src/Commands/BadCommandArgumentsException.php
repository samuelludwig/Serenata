<?php

namespace Serenata\Commands;

use RuntimeException;

/**
 * Indicates the arguments specified for executing a command are invalid or malformed.
 */
final class BadCommandArgumentsException extends RuntimeException
{
}
