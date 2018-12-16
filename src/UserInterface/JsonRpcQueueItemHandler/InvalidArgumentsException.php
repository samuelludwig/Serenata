<?php

namespace Serenata\UserInterface\JsonRpcQueueItemHandler;

use DomainException;

/**
 * Exception that indicates invalid arguments were passed for a command.
 */
final class InvalidArgumentsException extends DomainException
{
}
