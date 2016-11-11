<?php

namespace PhpIntegrator\UserInterface;

use Doctrine\Common\Cache\FilesystemCache;

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
 * Main application class.
 */
abstract class AbstractApplication
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @return ContainerBuilder
     */
    protected function getContainer()
    {
        if (!$this->container) {
            $this->container = new ContainerBuilder();

            $this->container
                ->register('application', AbstractApplication::class)
                ->setSynthetic(true);

            $this->container->set('application', $this);

            $this->container
                ->register('lexer', Lexer::class)
                ->addArgument([
                    'usedAttributes' => [
                        'comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'
                    ]
                ]);

            $this->container
                ->register('parser.phpParserFactory', ParserFactory::class);

            $this->container
                ->register('parser.phpParser', Parser::class)
                ->setFactory([new Reference('parser.phpParserFactory'), 'create'])
                ->setArguments([ParserFactory::PREFER_PHP7, new Reference('lexer'), [
                    'throwOnError' => false
                ]]);

            $this->container
                ->register('parser.cachingParserProxy', CachingParserProxy::class)
                ->addArgument(new Reference('parser.phpParser'));

            $this->container->setAlias('parser', 'parser.cachingParserProxy');

            $this->container
                ->register('cache', FilesystemCache::class)
                ->setArguments([new Expression("service('application').getCacheDirectory()")]);

            $this->container
                ->register('variableScanner', VariableScanner::class);

            $this->container
                ->register('typeAnalyzer', TypeAnalyzer::class);

            $this->container
                ->register('typeResolver', TypeResolver::class)
                ->setArguments([new Reference('typeAnalyzer')]);

            $this->container
                ->register('typeLocalizer', TypeLocalizer::class)
                ->setArguments([new Reference('typeAnalyzer')]);

            $this->container
                ->register('partialParser', PartialParser::class);

            $this->container
                ->register('sourceCodeStreamReader', SourceCodeStreamReader::class)
                ->setArguments([$this->stdinStream]);

            $this->container
                ->register('docblockParser', DocblockParser::class);

            $this->container
                ->register('docblockAnalyzer', DocblockAnalyzer::class);

            $this->container
                ->register('constantConverter', ConstantConverter::class);

            $this->container
                ->register('classlikeConstantConverter', ClasslikeConstantConverter::class);

            $this->container
                ->register('propertyConverter', PropertyConverter::class);

            $this->container
                ->register('classlikeConverter', ClasslikeConverter::class);

            $this->container
                ->register('functionConverter', FunctionConverter::class);

            $this->container
                ->register('methodConverter', MethodConverter::class);

            $this->container
                ->register('fileTypeResolverFactory', FileTypeResolverFactory::class)
                ->setArguments([new Reference('typeResolver'), new Reference('indexDatabase')]);

            $this->container
                ->register('projectTypeResolverFactory', ProjectTypeResolverFactory::class)
                ->setArguments([
                    new Reference('globalConstantExistanceChecker'),
                    new Reference('globalFunctionExistanceChecker'),
                    new Reference('indexDatabase')
                ]);

            $this->container
                ->register('projectTypeResolverFactoryFacade', ProjectTypeResolverFactoryFacade::class)
                ->setArguments([
                    new Reference('projectTypeResolverFactory'),
                    new Reference('fileTypeResolverFactory')
                ]);

            $this->container
                ->register('fileTypeLocalizerFactory', FileTypeLocalizerFactory::class)
                ->setArguments([new Reference('typeLocalizer'), new Reference('indexDatabase')]);

            $this->container
                ->register('inheritanceResolver', InheritanceResolver::class)
                ->setArguments([new Reference('docblockAnalyzer'), new Reference('typeAnalyzer')]);

            $this->container
                ->register('interfaceImplementationResolver', InterfaceImplementationResolver::class)
                ->setArguments([new Reference('docblockAnalyzer'), new Reference('typeAnalyzer')]);

            $this->container
                ->register('traitUsageResolver', TraitUsageResolver::class)
                ->setArguments([new Reference('docblockAnalyzer'), new Reference('typeAnalyzer')]);

            $this->container
                ->register('indexDatabase', IndexDatabase::class)
                ->setArguments([new Expression("service('application').getDatabaseFile()")]);

            $this->container
                ->register('classlikeInfoBuilderProviderCachingProxy', ClasslikeInfoBuilderProviderCachingProxy::class)
                ->setArguments([new Reference('indexDatabase'), new Reference('cache')]);

            $this->container
                ->setAlias('classlikeInfoBuilderProvider', 'classlikeInfoBuilderProviderCachingProxy');

            $this->container
                ->register('classlikeExistanceChecker', ClasslikeExistanceChecker::class)
                ->setArguments([new Reference('indexDatabase')]);

            $this->container
                ->register('globalFunctionExistanceChecker', GlobalFunctionExistanceChecker::class)
                ->setArguments([new Reference('indexDatabase')]);

            $this->container
                ->register('globalConstantExistanceChecker', GlobalConstantExistanceChecker::class)
                ->setArguments([new Reference('indexDatabase')]);

            $this->container
                ->register('storageForIndexers', CallbackStorageProxy::class)
                ->setArguments([new Reference('indexDatabase'), function ($fqcn) {
                    $provider = $this->container->get('classlikeInfoBuilderProvider');

                    if ($provider instanceof ClasslikeInfoBuilderProviderCachingProxy) {
                        $provider->clearCacheFor($fqcn);
                    }
                }]);

            $this->container
                ->register('classlikeInfoBuilder', ClasslikeInfoBuilder::class)
                ->setArguments([
                    new Reference('constantConverter'),
                    new Reference('classlikeConstantConverter'),
                    new Reference('propertyConverter'),
                    new Reference('functionConverter'),
                    new Reference('methodConverter'),
                    new Reference('classlikeConverter'),
                    new Reference('inheritanceResolver'),
                    new Reference('interfaceImplementationResolver'),
                    new Reference('traitUsageResolver'),
                    new Reference('classlikeInfoBuilderProvider'),
                    new Reference('typeAnalyzer')
                ]);

            $this->container
                ->register('typeDeducer', TypeDeducer::class)
                ->setArguments([
                    new Reference('parser'),
                    new Reference('classListCommand'),
                    new Reference('docblockParser'),
                    new Reference('partialParser'),
                    new Reference('typeAnalyzer'),
                    new Reference('typeResolver'),
                    new Reference('fileTypeResolverFactory'),
                    new Reference('indexDatabase'),
                    new Reference('classlikeInfoBuilder'),
                    new Reference('functionConverter'),
                    new Reference('constantConverter')
                ]);

            $this->container
                ->register('builtinIndexer', BuiltinIndexer::class)
                ->setArguments([
                    new Reference('indexDatabase'),
                    new Reference('typeAnalyzer'),
                    new Reference('typeDeducer')
                ]);

            $this->container
                ->register('fileIndexer', FileIndexer::class)
                ->setArguments([
                    new Reference('storageForIndexers'),
                    new Reference('typeAnalyzer'),
                    new Reference('typeResolver'),
                    new Reference('docblockParser'),
                    new Reference('typeDeducer'),
                    new Reference('parser')
                ]);

            $this->container
                ->register('projectIndexer', ProjectIndexer::class)
                ->setArguments([
                    new Reference('storageForIndexers'),
                    new Reference('fileIndexer'),
                    new Reference('sourceCodeStreamReader')
                ]);

            // Commands.
            $this->container
                ->register('initializeCommand', Command\InitializeCommand::class)
                ->setArguments([
                    new Reference('indexDatabase'),
                    new Reference('builtinIndexer'),
                    new Reference('projectIndexer')
                ]);

            $this->container
                ->register('reindexCommand', Command\ReindexCommand::class)
                ->setArguments([
                    new Reference('indexDatabase'),
                    new Reference('projectIndexer'),
                    new Reference('sourceCodeStreamReader')
                ]);

            $this->container
                ->register('vacuumCommand', Command\VacuumCommand::class)
                ->setArguments([new Reference('projectIndexer')]);

            $this->container
                ->register('truncateCommand', Command\TruncateCommand::class)
                ->setArguments([new Expression("service('application').getDatabaseFile()"), new Reference('cache')]);

            $this->container
                ->register('testCommand', Command\TestCommand::class)
                ->setArguments([new Reference('indexDatabase')]);

            $this->container
                ->register('classListCommand', Command\ClassListCommand::class)
                ->setArguments([
                    new Reference('constantConverter'),
                    new Reference('classlikeConstantConverter'),
                    new Reference('propertyConverter'),
                    new Reference('functionConverter'),
                    new Reference('methodConverter'),
                    new Reference('classlikeConverter'),
                    new Reference('inheritanceResolver'),
                    new Reference('interfaceImplementationResolver'),
                    new Reference('traitUsageResolver'),
                    new Reference('classlikeInfoBuilderProvider'),
                    new Reference('typeAnalyzer'),
                    new Reference('indexDatabase')
                ]);

            $this->container
                ->register('classInfoCommand', Command\ClassInfoCommand::class)
                ->setArguments([new Reference('typeAnalyzer'), new Reference('classlikeInfoBuilder')]);

            $this->container
                ->register('globalFunctionsCommand', Command\GlobalFunctionsCommand::class)
                ->setArguments([new Reference('functionConverter'), new Reference('indexDatabase')]);

            $this->container
                ->register('globalConstantsCommand', Command\GlobalConstantsCommand::class)
                ->setArguments([new Reference('constantConverter'), new Reference('indexDatabase')]);

            $this->container
                ->register('resolveTypeCommand', Command\ResolveTypeCommand::class)
                ->setArguments([new Reference('indexDatabase'), new Reference('ProjectTypeResolverFactoryFacade')]);

            $this->container
                ->register('localizeTypeCommand', Command\LocalizeTypeCommand::class)
                ->setArguments([new Reference('indexDatabase'), new Reference('fileTypeLocalizerFactory')]);

            $this->container
                ->register('semanticLintCommand', Command\SemanticLintCommand::class)
                ->setArguments([
                    new Reference('sourceCodeStreamReader'),
                    new Reference('parser'),
                    new Reference('fileTypeResolverFactory'),
                    new Reference('typeDeducer'),
                    new Reference('classlikeInfoBuilder'),
                    new Reference('docblockParser'),
                    new Reference('typeAnalyzer'),
                    new Reference('docblockAnalyzer'),
                    new Reference('classlikeExistanceChecker'),
                    new Reference('globalConstantExistanceChecker'),
                    new Reference('globalFunctionExistanceChecker')
                ]);

            $this->container
                ->register('availableVariablesCommand', Command\AvailableVariablesCommand::class)
                ->setArguments([
                    new Reference('variableScanner'),
                    new Reference('parser'),
                    new Reference('sourceCodeStreamReader')
                ]);

            $this->container
                ->register('deduceTypesCommand', Command\DeduceTypesCommand::class)
                ->setArguments([
                        new Reference('typeDeducer'),
                        new Reference('partialParser'),
                        new Reference('sourceCodeStreamReader')
                    ]);

            $this->container
                ->register('invocationInfoCommand', Command\InvocationInfoCommand::class)
                ->setArguments([new Reference('partialParser'), new Reference('sourceCodeStreamReader')]);

            $this->container
                ->register('namespaceListCommand', Command\NamespaceListCommand::class)
                ->setArguments([new Reference('indexDatabase')]);
        }

        return $this->container;
    }

    /**
     * @return string
     */
    public function getCacheDirectory()
    {
        $cachePath = sys_get_temp_dir() .
            '/php-integrator-base/' .
            get_current_user() . '/' .
            $this->getProjectName() . '/' .
            IndexDatabase::SCHEMA_VERSION .
            '/';

        if (!file_exists($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        return $cachePath;
    }

    /**
     * @return resource|null
     */
    abstract public function getStdinStream();

    /**
     * @return callable|null
     */
    abstract public function getProgressStreamingCallback();

    /**
     * @return string
     */
    abstract public function getDatabaseFile();

    /**
     * @return string
     */
    abstract public function getProjectName();
}
