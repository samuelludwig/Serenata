<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;

/**
 * Interface for commands.
 */
interface CommandInterface
{
    /**
     * Executes the command.
     *
     * @param ArrayAccess $arguments
     *
     * @throws InvalidArgumentsException
     *
     * @return string Output to return to the user.
     */
    public function execute(ArrayAccess $arguments): string;
}
