<?php

namespace PhpIntegrator\UserInterface;

use Exception;
use ArrayAccess;
use RuntimeException;
use UnexpectedValueException;

use Doctrine\Common\Cache\FilesystemCache;

use GetOptionKit\OptionParser;
use GetOptionKit\OptionCollection;

use PhpIntegrator\Analysis\VariableScanner;
use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\ClasslikeExistanceChecker;
use PhpIntegrator\Analysis\GlobalConstantExistanceChecker;
use PhpIntegrator\Analysis\GlobalFunctionExistanceChecker;

use PhpIntegrator\Analysis\Conversion\MethodConverter;
use PhpIntegrator\Analysis\Conversion\ConstantConverter;
use PhpIntegrator\Analysis\Conversion\PropertyConverter;
use PhpIntegrator\Analysis\Conversion\FunctionConverter;
use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;
use PhpIntegrator\Analysis\Conversion\ClasslikeConstantConverter;

use PhpIntegrator\Analysis\Relations\TraitUsageResolver;
use PhpIntegrator\Analysis\Relations\InheritanceResolver;
use PhpIntegrator\Analysis\Relations\InterfaceImplementationResolver;

use PhpIntegrator\Analysis\Typing\TypeDeducer;
use PhpIntegrator\Analysis\Typing\TypeResolver;
use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\TypeLocalizer;
use PhpIntegrator\Analysis\Typing\FileTypeResolverFactory;
use PhpIntegrator\Analysis\Typing\FileTypeLocalizerFactory;
use PhpIntegrator\Analysis\Typing\ProjectTypeResolverFactory;
use PhpIntegrator\Analysis\Typing\ProjectTypeResolverFactoryFacade;

use PhpIntegrator\Indexing\FileIndexer;
use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\ProjectIndexer;
use PhpIntegrator\Indexing\BuiltinIndexer;
use PhpIntegrator\Indexing\CallbackStorageProxy;

use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\DocblockParser;
use PhpIntegrator\Parsing\CachingParserProxy;

use PhpIntegrator\Utility\SourceCodeStreamReader;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\ExpressionLanguage\Expression;

/**
 * Command line extension of the application class.
 */
class CliApplication extends Application
{
    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $databaseFile;

    /**
     * @var resource|null
     */
    protected $stdinStream;

    /**
     * Handles the application process.
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
            throw new UnexpectedValueException(
                'Not enough argument supplied. Usage: . <project> <command> [<addtional parameters>]'
            );
        }

        $programName = array_shift($arguments);
        $this->projectName = array_shift($arguments);
        $command = array_shift($arguments);

        // This seems to be needed for GetOptionKit.
        array_unshift($arguments, $programName);

        $commandServiceMap = [
            '--initialize'          => 'initializeCommand',
            '--reindex'             => 'reindexCommand',
            '--vacuum'              => 'vacuumCommand',
            '--truncate'            => 'truncateCommand',

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
                $this->databaseFile = mb_substr($argument, mb_strlen('--database='));
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

            if (!isset($processedArguments['database'])) {
                return 'No database path passed!';
            }

            $output = $this->handle($command, $processedArguments);

            return $this->handle($command, $processedArguments);
        }

        $supportedCommands = implode(', ', array_keys($commandServiceMap));

        echo "Unknown command {$command}, supported commands: {$supportedCommands}";
    }

    /**
     * @inheritDoc
     */
    public function handle(Command\CommandInterface $command, ArrayAccess $arguments)
    {
        $result = null;
        $success = false;

        try {
            $result = $command->execute($arguments);
            $success = true;
        } catch (Command\InvalidArgumentsException $e) {
            $result = $e->getMessage();
        } catch (Exception $e) {
            $result = $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage();
        } catch (\Throwable $e) {
            // On PHP < 7, throwable simply won't exist and this clause is never triggered.
            $result = $e->getFile() . ':' . $e->getLine() . ' - ' . $e->getMessage();
        }

        return $this->outputJson($success, $result);
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
     * @inheritDoc
     */
    public function getDatabaseFile()
    {
        return $this->databaseFile;
    }

    /**
     * @inheritDoc
     */
    public function getProjectName()
    {
        return $this->projectName;
    }
}
