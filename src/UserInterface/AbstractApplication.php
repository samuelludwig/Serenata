<?php

namespace PhpIntegrator\UserInterface;

use Doctrine\Common\Cache\ArrayCache;

use PhpIntegrator\Analysis\VariableScanner;
use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\ClearableCacheCollection;
use PhpIntegrator\Analysis\CachingClasslikeExistanceChecker;
use PhpIntegrator\Analysis\CachingGlobalConstantExistanceChecker;
use PhpIntegrator\Analysis\CachingGlobalFunctionExistanceChecker;

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
use PhpIntegrator\Analysis\Typing\FileClassListProviderCachingDecorator;
use PhpIntegrator\Analysis\Typing\FileTypeResolverFactoryCachingDecorator;

use PhpIntegrator\Indexing\Indexer;
use PhpIntegrator\Indexing\FileIndexer;
use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\ProjectIndexer;
use PhpIntegrator\Indexing\BuiltinIndexer;
use PhpIntegrator\Indexing\CallbackStorageProxy;

use PhpIntegrator\Mediating\CacheClearingEventMediator;

use PhpIntegrator\Parsing\PrettyPrinter;
use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\DocblockParser;
use PhpIntegrator\Parsing\CachingParserProxy;

use PhpIntegrator\Utility\SourceCodeStreamReader;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
     * The path to the database to use.
     *
     * @var string
     */
    protected $databaseFile;

    /**
     * @return ContainerBuilder
     */
    protected function getContainer()
    {
        if (!$this->container) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    /**
     * @return ContainerBuilder
     */
    protected function createContainer()
    {
        $container = new ContainerBuilder();

        $this->registerServices($container);

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerServices(ContainerBuilder $container)
    {
        $container
            ->register('application', AbstractApplication::class)
            ->setSynthetic(true);

        $container->set('application', $this);

        $container
            ->register('lexer', Lexer::class)
            ->addArgument([
                'usedAttributes' => [
                    'comments', 'startLine', 'endLine', 'startFilePos', 'endFilePos'
                ]
            ]);

        $container
            ->register('parser.phpParserFactory', ParserFactory::class);

        $container
            ->register('parser.phpParser', Parser::class)
            ->setFactory([new Reference('parser.phpParserFactory'), 'create'])
            ->setArguments([ParserFactory::PREFER_PHP7, new Reference('lexer'), [
                'throwOnError' => false
            ]]);

        $container
            ->register('parser.cachingParserProxy', CachingParserProxy::class)
            ->addArgument(new Reference('parser.phpParser'));

        $container->setAlias('parser', 'parser.cachingParserProxy');

        $container
            ->register('cache', ArrayCache::class);

        $container
            ->register('variableScanner', VariableScanner::class);

        $container
            ->register('typeAnalyzer', TypeAnalyzer::class);

        $container
            ->register('typeResolver', TypeResolver::class)
            ->setArguments([new Reference('typeAnalyzer')]);

        $container
            ->register('typeLocalizer', TypeLocalizer::class)
            ->setArguments([new Reference('typeAnalyzer')]);

        $container
            ->register('prettyPrinter', PrettyPrinter::class);

        $container
            ->register('partialParser', PartialParser::class)
            ->setArguments([new Reference('parser.phpParserFactory'), new Reference('prettyPrinter')]);

        $container
            ->register('sourceCodeStreamReader', SourceCodeStreamReader::class)
            ->setArguments([$this->stdinStream]);

        $container
            ->register('docblockParser', DocblockParser::class);

        $container
            ->register('docblockAnalyzer', DocblockAnalyzer::class);

        $container
            ->register('constantConverter', ConstantConverter::class);

        $container
            ->register('classlikeConstantConverter', ClasslikeConstantConverter::class);

        $container
            ->register('propertyConverter', PropertyConverter::class);

        $container
            ->register('classlikeConverter', ClasslikeConverter::class);

        $container
            ->register('functionConverter', FunctionConverter::class);

        $container
            ->register('methodConverter', MethodConverter::class);

        $container
            ->setAlias('fileClassListProvider.instance', 'classListCommand');

        $container
            ->register('fileClassListProvider.cachingDecorator', FileClassListProviderCachingDecorator::class)
            ->setArguments([new Reference('fileClassListProvider.instance')]);

        $container
            ->setAlias('fileClassListProvider', 'fileClassListProvider.cachingDecorator');

        $container
            ->register('fileTypeResolverFactory.instance', FileTypeResolverFactory::class)
            ->setArguments([new Reference('typeResolver'), new Reference('indexDatabase')]);

        $container
            ->register('fileTypeResolverFactory.cachingDecorator', FileTypeResolverFactoryCachingDecorator::class)
            ->setArguments([new Reference('fileTypeResolverFactory.instance')]);

        $container
            ->setAlias('fileTypeResolverFactory', 'fileTypeResolverFactory.cachingDecorator');

        $container
            ->register('projectTypeResolverFactory', ProjectTypeResolverFactory::class)
            ->setArguments([
                new Reference('globalConstantExistanceChecker'),
                new Reference('globalFunctionExistanceChecker'),
                new Reference('indexDatabase')
            ]);

        $container
            ->register('projectTypeResolverFactoryFacade', ProjectTypeResolverFactoryFacade::class)
            ->setArguments([
                new Reference('projectTypeResolverFactory'),
                new Reference('fileTypeResolverFactory')
            ]);

        $container
            ->register('fileTypeLocalizerFactory', FileTypeLocalizerFactory::class)
            ->setArguments([new Reference('typeLocalizer'), new Reference('indexDatabase')]);

        $container
            ->register('inheritanceResolver', InheritanceResolver::class)
            ->setArguments([new Reference('docblockAnalyzer'), new Reference('typeAnalyzer')]);

        $container
            ->register('interfaceImplementationResolver', InterfaceImplementationResolver::class)
            ->setArguments([new Reference('docblockAnalyzer'), new Reference('typeAnalyzer')]);

        $container
            ->register('traitUsageResolver', TraitUsageResolver::class)
            ->setArguments([new Reference('docblockAnalyzer'), new Reference('typeAnalyzer')]);

        $container
            ->register('indexDatabase', IndexDatabase::class);

        $container
            ->register('classlikeInfoBuilderProviderCachingProxy', ClasslikeInfoBuilderProviderCachingProxy::class)
            ->setArguments([new Reference('indexDatabase'), new Reference('cache')]);

        $container
            ->setAlias('classlikeInfoBuilderProvider', 'classlikeInfoBuilderProviderCachingProxy');

        $container
            ->register('classlikeExistanceChecker', CachingClasslikeExistanceChecker::class)
            ->setArguments([new Reference('indexDatabase')]);

        $container
            ->register('globalFunctionExistanceChecker', CachingGlobalFunctionExistanceChecker::class)
            ->setArguments([new Reference('indexDatabase')]);

        $container
            ->register('globalConstantExistanceChecker', CachingGlobalConstantExistanceChecker::class)
            ->setArguments([new Reference('indexDatabase')]);

        $container
            ->register('cacheClearingEventMediator.clearableCache', ClearableCacheCollection::class)
            ->setArguments([[
                new Reference('classlikeExistanceChecker'),
                new Reference('globalFunctionExistanceChecker'),
                new Reference('globalConstantExistanceChecker'),
                new Reference('fileTypeResolverFactory.cachingDecorator'),
                new Reference('fileClassListProvider.cachingDecorator')
            ]]);

        $container
            ->register('cacheClearingEventMediator', CacheClearingEventMediator::class)
            ->setArguments([
                new Reference('cacheClearingEventMediator.clearableCache'),
                new Reference('indexer'),
                Indexer::INDEXING_SUCCEEDED_EVENT
            ]);

        $container
            ->register('storageForIndexers', CallbackStorageProxy::class)
            ->setArguments([new Reference('indexDatabase'), function ($fqcn) use ($container) {
                $provider = $container->get('classlikeInfoBuilderProvider');

                if ($provider instanceof ClasslikeInfoBuilderProviderCachingProxy) {
                    $provider->clearCacheFor($fqcn);
                }
            }]);

        $container
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

        $container
            ->register('typeDeducer', TypeDeducer::class)
            ->setArguments([
                new Reference('parser'),
                new Reference('fileClassListProvider'),
                new Reference('docblockParser'),
                new Reference('partialParser'),
                new Reference('typeAnalyzer'),
                new Reference('typeResolver'),
                new Reference('fileTypeResolverFactory'),
                new Reference('indexDatabase'),
                new Reference('classlikeInfoBuilder'),
                new Reference('functionConverter'),
                new Reference('constantConverter'),
                new Reference('prettyPrinter')
            ]);

        $container
            ->register('builtinIndexer', BuiltinIndexer::class)
            ->setArguments([
                new Reference('indexDatabase'),
                new Reference('typeAnalyzer'),
                new Reference('partialParser'),
                new Reference('typeDeducer')
            ]);

        $container
            ->register('fileIndexer', FileIndexer::class)
            ->setArguments([
                new Reference('storageForIndexers'),
                new Reference('typeAnalyzer'),
                new Reference('typeResolver'),
                new Reference('docblockParser'),
                new Reference('partialParser'),
                new Reference('typeDeducer'),
                new Reference('parser')
            ]);

        $container
            ->register('projectIndexer', ProjectIndexer::class)
            ->setArguments([
                new Reference('storageForIndexers'),
                new Reference('fileIndexer'),
                new Reference('sourceCodeStreamReader')
            ]);

        $container
            ->register('indexer', Indexer::class)
            ->setArguments([
                new Reference('projectIndexer'),
                new Reference('sourceCodeStreamReader')
            ]);

        $this->registerCommandServices($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerCommandServices(ContainerBuilder $container)
    {
        $container
            ->register('initializeCommand', Command\InitializeCommand::class)
            ->setArguments([
                new Reference('indexDatabase'),
                new Reference('builtinIndexer'),
                new Reference('projectIndexer'),
                new Reference('cache')
            ]);

        $container
            ->register('reindexCommand', Command\ReindexCommand::class)
            ->setArguments([
                new Reference('indexer')
            ]);

        $container
            ->register('vacuumCommand', Command\VacuumCommand::class)
            ->setArguments([new Reference('projectIndexer')]);

        $container
            ->register('testCommand', Command\TestCommand::class)
            ->setArguments([new Reference('indexDatabase')]);

        $container
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

        $container
            ->register('classInfoCommand', Command\ClassInfoCommand::class)
            ->setArguments([new Reference('typeAnalyzer'), new Reference('classlikeInfoBuilder')]);

        $container
            ->register('globalFunctionsCommand', Command\GlobalFunctionsCommand::class)
            ->setArguments([new Reference('functionConverter'), new Reference('indexDatabase')]);

        $container
            ->register('globalConstantsCommand', Command\GlobalConstantsCommand::class)
            ->setArguments([new Reference('constantConverter'), new Reference('indexDatabase')]);

        $container
            ->register('resolveTypeCommand', Command\ResolveTypeCommand::class)
            ->setArguments([new Reference('indexDatabase'), new Reference('ProjectTypeResolverFactoryFacade')]);

        $container
            ->register('localizeTypeCommand', Command\LocalizeTypeCommand::class)
            ->setArguments([new Reference('indexDatabase'), new Reference('fileTypeLocalizerFactory')]);

        $container
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

        $container
            ->register('availableVariablesCommand', Command\AvailableVariablesCommand::class)
            ->setArguments([
                new Reference('variableScanner'),
                new Reference('parser'),
                new Reference('sourceCodeStreamReader')
            ]);

        $container
            ->register('deduceTypesCommand', Command\DeduceTypesCommand::class)
            ->setArguments([
                    new Reference('typeDeducer'),
                    new Reference('partialParser'),
                    new Reference('sourceCodeStreamReader')
                ]);

        $container
            ->register('invocationInfoCommand', Command\InvocationInfoCommand::class)
            ->setArguments([new Reference('partialParser'), new Reference('sourceCodeStreamReader')]);

        $container
            ->register('namespaceListCommand', Command\NamespaceListCommand::class)
            ->setArguments([new Reference('indexDatabase')]);
    }

    /**
     * @return mixed
     */
    abstract public function run();

    /**
     * @return resource|null
     */
    abstract public function getStdinStream();

    /**
     * @param string $databaseFile
     *
     * @return static
     */
    public function setDatabaseFile($databaseFile)
    {
        $indexDatabase = $this->getContainer()->get('indexDatabase');

        if ($indexDatabase->getDatabasePath() !== $databaseFile) {
            $indexDatabase->setDatabasePath($databaseFile);

            $this->getContainer()->get('cacheClearingEventMediator.clearableCache')->clearCache();
        }

        return $this;
    }
}
