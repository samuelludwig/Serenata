<?php

namespace Serenata\Commands;

use DomainException;

use Serenata\Common\Position;

use Serenata\Utility\CommandInterface;

/**
 * Creates a(n LSP) command from raw data.
 */
final class CommandFactory
{
    /**
     * @var string
     */
    private const DUMMY_TITLE = 'Dummy title from CommandFactory';

    /**
     * @param string                   $name
     * @param array<string,mixed>|null $arguments
     *
     * @throws BadCommandArgumentsException
     *
     * @return CommandInterface
     */
    public function create(string $name, ?array $arguments): CommandInterface
    {
        $normalizedArguments = $arguments !== null ? $arguments : [];

        if ($name === OpenTextDocumentCommand::getCommandName()) {
            return new OpenTextDocumentCommand(
                self::DUMMY_TITLE,
                $this->expectUri($normalizedArguments, 'uri'),
                $this->expectPosition($normalizedArguments, 'position')
            );
        }

        throw new DomainException(
            'Don\'t know how to handle commands of type "' . $name . '"'
        );
    }

    /**
     * @param array<string,mixed> $arguments
     * @param string              $key
     *
     * @throws BadCommandArgumentsException
     *
     * @return string
     */
    private function expectUri(array $arguments, string $key): string
    {
        return $this->expectString($arguments, $key);
    }

    /**
     * @param array<string,mixed> $arguments
     * @param string              $key
     *
     * @throws BadCommandArgumentsException
     *
     * @return Position
     */
    private function expectPosition(array $arguments, string $key): Position
    {
        $value = $this->expectMap($arguments, $key);

        return new Position($this->expectInt($value, 'line'), $this->expectInt($value, 'character'));
    }

    /**
     * @param array<string,mixed> $arguments
     * @param string              $key
     *
     * @throws BadCommandArgumentsException
     *
     * @return array<string,mixed>
     */
    private function expectMap(array $arguments, string $key): array
    {
        $value = $this->get($arguments, $key);

        if (!is_array($value)) {
            throw new BadCommandArgumentsException(
                'Expected argument "' . $key . '" to be an associative array, got "' . gettype($value) . '" instead'
            );
        }

        return $value;
    }

    // /**
    //  * @param array<string,mixed> $arguments
    //  * @param string              $key
    //  *
    //  * @throws BadCommandArgumentsException
    //  *
    //  * @return mixed[]
    //  */
    // private function expectArray(array $arguments, string $key): array
    // {
    //     $value = $this->get($arguments, $key);
    //
    //     if (!is_array($value)) {
    //         throw new BadCommandArgumentsException(
    //             'Expected argument "' . $key . '" to be an array, got "' . gettype($value) . '" instead'
    //         );
    //     }
    //
    //     return $value;
    // }

    /**
     * @param array<string,mixed> $arguments
     * @param string              $key
     *
     * @throws BadCommandArgumentsException
     *
     * @return int
     */
    private function expectInt(array $arguments, string $key): int
    {
        $value = $this->get($arguments, $key);

        if (!is_integer($value)) {
            throw new BadCommandArgumentsException(
                'Expected argument "' . $key . '" to be an integer, got "' . gettype($value) . '" instead'
            );
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $arguments
     * @param string              $key
     *
     * @throws BadCommandArgumentsException
     *
     * @return string
     */
    private function expectString(array $arguments, string $key): string
    {
        $value = $this->get($arguments, $key);

        if (!is_string($value)) {
            throw new BadCommandArgumentsException(
                'Expected argument "' . $key . '" to be a string, got "' . gettype($value) . '" instead'
            );
        }

        return $value;
    }

    /**
     * @param array<string,mixed> $arguments
     * @param string              $key
     *
     * @throws BadCommandArgumentsException
     *
     * @return mixed
     */
    private function get(array $arguments, string $key)
    {
        if (!array_key_exists($key, $arguments)) {
            throw new BadCommandArgumentsException('Expected argument "' . $key . '" to exist');
        }

        return $arguments[$key];
    }
}
