<?php

namespace PhpIntegrator\UserInterface;

use Doctrine\Common\Cache\ArrayCache;

use PhpIntegrator\Analysis\VariableScanner;
use PhpIntegrator\Analysis\DocblockAnalyzer;
use PhpIntegrator\Analysis\ClassListProvider;
use PhpIntegrator\Analysis\ClasslikeInfoBuilder;
use PhpIntegrator\Analysis\ClearableCacheInterface;
use PhpIntegrator\Analysis\GlobalFunctionsProvider;
use PhpIntegrator\Analysis\GlobalConstantsProvider;
use PhpIntegrator\Analysis\ClearableCacheCollection;
use PhpIntegrator\Analysis\ClasslikeInfoBuilderProvider;
use PhpIntegrator\Analysis\FilePositionClasslikeDeterminer;
use PhpIntegrator\Analysis\CachingClasslikeExistenceChecker;
use PhpIntegrator\Analysis\CachingGlobalConstantExistenceChecker;
use PhpIntegrator\Analysis\CachingGlobalFunctionExistenceChecker;

use PhpIntegrator\Analysis\Conversion\MethodConverter;
use PhpIntegrator\Analysis\Conversion\ConstantConverter;
use PhpIntegrator\Analysis\Conversion\PropertyConverter;
use PhpIntegrator\Analysis\Conversion\FunctionConverter;
use PhpIntegrator\Analysis\Conversion\ClasslikeConverter;
use PhpIntegrator\Analysis\Conversion\ClasslikeConstantConverter;

use PhpIntegrator\Analysis\Node\NameNodeFqsenDeterminer;
use PhpIntegrator\Analysis\Node\ConstNameNodeFqsenDeterminer;
use PhpIntegrator\Analysis\Node\FunctionFunctionInfoRetriever;
use PhpIntegrator\Analysis\Node\MethodCallMethodInfoRetriever;
use PhpIntegrator\Analysis\Node\FunctionNameNodeFqsenDeterminer;
use PhpIntegrator\Analysis\Node\PropertyFetchPropertyInfoRetriever;

use PhpIntegrator\Analysis\Relations\TraitUsageResolver;
use PhpIntegrator\Analysis\Relations\InheritanceResolver;
use PhpIntegrator\Analysis\Relations\InterfaceImplementationResolver;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;
use PhpIntegrator\Analysis\Typing\FileClassListProviderCachingDecorator;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\LocalTypeScanner;
use PhpIntegrator\Analysis\Typing\Deduction\NewNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\NameNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\SelfNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ArrayNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\CloneNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\CatchNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\AssignNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ParentNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\StaticNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\StringNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ClosureNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\DNumberNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\LNumberNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\TernaryNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\FuncCallNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\VariableNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ClassLikeNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\MethodCallNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ConstFetchNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ExpressionLocalTypeAnalyzer;
use PhpIntegrator\Analysis\Typing\Deduction\PropertyFetchNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ArrayDimFetchNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ClassConstFetchNodeTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ForeachNodeLoopValueTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\FunctionLikeParameterTypeDeducer;
use PhpIntegrator\Analysis\Typing\Deduction\ConfigurableDelegatingNodeTypeDeducer;

use PhpIntegrator\Analysis\Typing\Localization\TypeLocalizer;
use PhpIntegrator\Analysis\Typing\Localization\FileTypeLocalizerFactory;

use PhpIntegrator\Analysis\Typing\Resolving\TypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\DocblockTypeResolver;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactory;
use PhpIntegrator\Analysis\Typing\Resolving\ProjectTypeResolverFactory;
use PhpIntegrator\Analysis\Typing\Resolving\ProjectTypeResolverFactoryFacade;
use PhpIntegrator\Analysis\Typing\Resolving\FileTypeResolverFactoryCachingDecorator;

use PhpIntegrator\Indexing\Indexer;
use PhpIntegrator\Indexing\FileIndexer;
use PhpIntegrator\Indexing\IndexDatabase;
use PhpIntegrator\Indexing\ProjectIndexer;
use PhpIntegrator\Indexing\BuiltinIndexer;
use PhpIntegrator\Indexing\CallbackStorageProxy;

use PhpIntegrator\Linting\Linter;
use PhpIntegrator\Linting\UnknownClassAnalyzerFactory;
use PhpIntegrator\Linting\UnknownMemberAnalyzerFactory;
use PhpIntegrator\Linting\UnusedUseStatementAnalyzerFactory;
use PhpIntegrator\Linting\DocblockCorrectnessAnalyzerFactory;
use PhpIntegrator\Linting\UnknownGlobalFunctionAnalyzerFactory;
use PhpIntegrator\Linting\UnknownGlobalConstantAnalyzerFactory;

use PhpIntegrator\Mediating\CacheClearingEventMediator;

use PhpIntegrator\Parsing\PrettyPrinter;
use PhpIntegrator\Parsing\PartialParser;
use PhpIntegrator\Parsing\DocblockParser;
use PhpIntegrator\Parsing\ParserTokenHelper;
use PhpIntegrator\Parsing\CachingParserProxy;
use PhpIntegrator\Parsing\LastExpressionParser;

use PhpIntegrator\PrettyPrinting\FunctionParameterPrettyPrinter;

use PhpIntegrator\SignatureHelp\SignatureHelpRetriever;

use PhpIntegrator\Tooltips\TooltipProvider;
use PhpIntegrator\Tooltips\PropertyTooltipGenerator;
use PhpIntegrator\Tooltips\NameNodeTooltipGenerator;
use PhpIntegrator\Tooltips\ConstantTooltipGenerator;
use PhpIntegrator\Tooltips\FunctionTooltipGenerator;
use PhpIntegrator\Tooltips\ClassLikeTooltipGenerator;
use PhpIntegrator\Tooltips\FunctionNodeTooltipGenerator;
use PhpIntegrator\Tooltips\FuncCallNodeTooltipGenerator;
use PhpIntegrator\Tooltips\MethodCallNodeTooltipGenerator;
use PhpIntegrator\Tooltips\ConstFetchNodeTooltipGenerator;
use PhpIntegrator\Tooltips\ClassMethodNodeTooltipGenerator;
use PhpIntegrator\Tooltips\PropertyFetchNodeTooltipGenerator;
use PhpIntegrator\Tooltips\ClassConstFetchNodeTooltipGenerator;
use PhpIntegrator\Tooltips\StaticMethodCallNodeTooltipGenerator;
use PhpIntegrator\Tooltips\StaticPropertyFetchNodeTooltipGenerator;

use PhpIntegrator\Utility\SourceCodeStreamReader;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory;

use Symfony\Component\Config\FileLocator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Main application class.
 */
abstract class AbstractApplication
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * The path to the database to use.
     *
     * @var string
     */
    private $databaseFile;

    /**
     * @return ContainerBuilder
     */
    protected function getContainer(): ContainerBuilder
    {
        if (!$this->container) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    /**
     * @return ContainerBuilder
     */
    protected function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $this->registerYamlServices($container);
        $this->registerServices($container);

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerYamlServices(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/services'));
        $loader->load('Main.yml');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function registerServices(ContainerBuilder $container): void
    {
        $container
            ->register('application', AbstractApplication::class)
            ->setSynthetic(true);

        $container->set('application', $this);

        $container
            ->register('sourceCodeStreamReader', SourceCodeStreamReader::class)
            ->setArguments([$this->getStdinStream()]);

        $container
            ->register('storageForIndexers', CallbackStorageProxy::class)
            ->setArguments([new Reference('indexDatabase'), function ($fqcn) use ($container) {
                $provider = $container->get('classlikeInfoBuilderProvider');

                if ($provider instanceof ClasslikeInfoBuilderProviderCachingProxy) {
                    $provider->clearCacheFor($fqcn);
                }
            }]);

        $container
            ->register('nodeTypeDeducer.configurableDelegator', ConfigurableDelegatingNodeTypeDeducer::class)
            ->setArguments([])
            ->setConfigurator(function (ConfigurableDelegatingNodeTypeDeducer $configurableDelegatingNodeTypeDeducer) use ($container) {
                // Avoid circular references due to two-way object usage.
                $configurableDelegatingNodeTypeDeducer->setNodeTypeDeducer($container->get('nodeTypeDeducer.instance'));
            });
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
    public function setDatabaseFile(string $databaseFile)
    {
        /** @var IndexDatabase $indexDatabase */
        $indexDatabase = $this->getContainer()->get('indexDatabase');

        if (!$indexDatabase->hasDatabasePathConfigured() || $indexDatabase->getDatabasePath() !== $databaseFile) {
            $indexDatabase->setDatabasePath($databaseFile);

            /** @var ClearableCacheInterface $clearableCache */
            $clearableCache = $this->getContainer()->get('cacheClearingEventMediator.clearableCache');
            $clearableCache->clearCache();
        }

        return $this;
    }
}
