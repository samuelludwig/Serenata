<?php

namespace PhpIntegrator\UserInterface;

use ArrayAccess;
use ArrayObject;
use RuntimeException;

use GetOptionKit\Option;
use GetOptionKit\OptionParser;
use GetOptionKit\OptionCollection;

/**
 * Command line extension of the application class.
 */
class CliApplication extends AbstractApplication
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var resource|null
     */
    protected $stdinStream;

    /**
     * Handles a command based on command line parameters.
     *
     * @param array         $arguments   The arguments to pass.
     * @param resource|null $stdinStream The stream t use to read STDIN data from when requested for commands.
     *
     * @return mixed
     */
    public function handleCommandLineArguments(array $arguments, $stdinStream = null)
    {
        $this->stdinStream = $stdinStream;

        if (count($arguments) < 3) {
            echo 'Not enough argument supplied. Usage: . <project> <command> [<additional parameters>]';
        }

        $programName = array_shift($arguments);
        $this->projectName = array_shift($arguments);
        $command = array_shift($arguments);

        $this->getContainer()->get('indexer')->setProgressStreamingCallback(
            $this->getProgressStreamingCallback()
        );

        // This seems to be needed for GetOptionKit.
        array_unshift($arguments, $programName);

        $commandServiceMap = [
            '--initialize'          => 'initializeCommand',
            '--reindex'             => 'reindexCommand',
            '--vacuum'              => 'vacuumCommand',
            '--test'                => 'testCommand',

            '--class-list'          => 'classListCommand',
            '--class-info'          => 'classInfoCommand',
            '--functions'           => 'globalFunctionsCommand',
            '--constants'           => 'globalConstantsCommand',
            '--resolve-type'        => 'resolveTypeCommand',
            '--localize-type'       => 'localizeTypeCommand',
            '--semantic-lint'       => 'semanticLintCommand',
            '--available-variables' => 'availableVariablesCommand',
            '--deduce-types'        => 'deduceTypesCommand',
            '--invocation-info'     => 'invocationInfoCommand',
            '--namespace-list'      => 'namespaceListCommand'
        ];

        $optionCollection = new OptionCollection();
        $optionCollection->add('database:', 'The index database to use.' )->isa('string');

        foreach ($arguments as $argument) {
            if (mb_strpos($argument, '--database=') === 0) {
                $this->setDatabaseFile(mb_substr($argument, mb_strlen('--database=')));
            }
        }

        if (isset($commandServiceMap[$command])) {
            $container = $this->getContainer();

            /** @var \PhpIntegrator\UserInterface\Command\CommandInterface $command */
            $command = $container->get($commandServiceMap[$command]);
            $command->attachOptions($optionCollection);

            $parser = new OptionParser($optionCollection);

            $processedArguments = null;

            try {
                $processedArguments = $parser->parse($arguments);
            } catch(\Exception $e) {
                return $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage();
            }

            $simplifiedOptions = $this->getSimplifiedOptions($processedArguments);

            return $this->handleCommand($command, $simplifiedOptions);
        }

        $supportedCommands = implode(', ', array_keys($commandServiceMap));

        echo "Unknown command {$command}, supported commands: {$supportedCommands}";
    }

    /**
     * @inheritDoc
     */
    public function handleCommand(Command\CommandInterface $command, ArrayAccess $arguments)
    {
        $result = null;
        $success = false;

        try {
            $result = $command->execute($arguments);
            $success = true;
        } catch (Command\InvalidArgumentsException $e) {
            $result = $e->getMessage();
        } catch (\Exception $e) {
            $result = $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage();
        } catch (\Throwable $e) {
            // On PHP < 7, throwable simply won't exist and this clause is never triggered.
            $result = $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage();
        }

        return $this->outputJson($success, $result);
    }

    /**
     * @param ArrayAccess $processedOptions
     *
     * @return ArrayObject
     */
    protected function getSimplifiedOptions(ArrayAccess $processedOptions)
    {
        $options = [];

        foreach ($processedOptions as $key => $option) {
            if ($option instanceof Option) {
                $options[$key] = $option->value;
            } else {
                $options[$key] = $option;
            }
        }

        return new ArrayObject($options);
    }

    /**
     * Outputs JSON.
     *
     * @param bool  $success
     * @param mixed $data
     *
     * @throws RuntimeException When the encoding fails, which should never happen.
     *
     * @return string
     */
    protected function outputJson($success, $data)
    {
        $output = json_encode([
            'success' => $success,
            'result'  => $data
        ]);

        if (!$output) {
            $errorMessage = json_last_error_msg() ?: 'Unknown';

            throw new RuntimeException(
                'The encoded JSON output was empty, something must have gone wrong! The error message was: ' .
                '"' .
                $errorMessage .
                '"'
            );
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getStdinStream()
    {
        return $this->stdinStream;
    }

    /**
     * @return callable
     */
    public function getProgressStreamingCallback()
    {
        return function ($progress) {
            // Yes, we abuse the error channel for this.
            fwrite(STDERR, $progress . PHP_EOL);
        };
    }

    /**
     * @inheritDoc
     */
    public function getProjectName()
    {
        return $this->projectName;
    }
}
