<?php

namespace PhpIntegrator\UserInterface\Command;

use ArrayAccess;
use RuntimeException;

use GetOptionKit\OptionCollection;

/**
 * Base class for commands.
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * Sets up command line arguments expected by the command.
     *
     * Operates as a(n optional) template method.
     *
     * @param OptionCollection $optionCollection
     */
    public function attachOptions(OptionCollection $optionCollection)
    {

    }

    /**
     * Executes the actual command and processes the specified arguments.
     *
     * Operates as a template method.
     *
     * @param ArrayAccess $arguments
     *
     * @throws InvalidArgumentsException
     *
     * @return string Output to pass back.
     */
    abstract public function execute(ArrayAccess $arguments);
}
