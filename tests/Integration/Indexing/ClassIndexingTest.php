<?php

namespace Serenata\Tests\Integration\Indexing;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ClassIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleClass(): void
    {
        $structure = $this->indexClass('SimpleClass.phpt');

        self::assertSame('Test', $structure->getName());
        self::assertSame('\Test', $structure->getFqcn());
        self::assertSame($this->normalizePath($this->getPathFor('SimpleClass.phpt')), $structure->getFile()->getUri());
        self::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $structure->getRange()
        );
        self::assertNull($structure->getShortDescription());
        self::assertNull($structure->getLongDescription());
        self::assertFalse($structure->getIsDeprecated());
        self::assertFalse($structure->getHasDocblock());
        self::assertCount(1, $structure->getConstants());
        self::assertEmpty($structure->getProperties());
        self::assertEmpty($structure->getMethods());
        self::assertFalse($structure->getIsAbstract());
        self::assertFalse($structure->getIsFinal());
        self::assertFalse($structure->getIsAnnotation());
        self::assertNull($structure->getParentFqcn());
        self::assertEmpty($structure->getChildFqcns());
        self::assertEmpty($structure->getInterfaceFqcns());
        self::assertEmpty($structure->getTraitFqcns());
        self::assertEmpty($structure->getTraitAliases());
        self::assertEmpty($structure->getTraitPrecedences());
    }

    /**
     * @return void
     */
    public function testClassNamespace(): void
    {
        $structure = $this->indexClass('ClassNamespace.phpt');

        self::assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testClassShortDescription(): void
    {
        $structure = $this->indexClass('ClassShortDescription.phpt');

        self::assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testClassLongDescription(): void
    {
        $structure = $this->indexClass('ClassLongDescription.phpt');

        self::assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedClass(): void
    {
        $structure = $this->indexClass('DeprecatedClass.phpt');

        self::assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testClassWithDocblock(): void
    {
        $structure = $this->indexClass('ClassWithDocblock.phpt');

        self::assertTrue($structure->getHasDocblock());
    }

    /**
     * @return void
     */
    public function testAbstractClass(): void
    {
        $structure = $this->indexClass('AbstractClass.phpt');

        self::assertTrue($structure->getIsAbstract());
    }

    /**
     * @return void
     */
    public function testFinalClass(): void
    {
        $structure = $this->indexClass('FinalClass.phpt');

        self::assertTrue($structure->getIsFinal());
    }

    /**
     * @return void
     */
    public function testAnnotationClass(): void
    {
        $structure = $this->indexClass('AnnotationClass.phpt');

        self::assertTrue($structure->getIsAnnotation());
    }

    /**
     * @return void
     */
    public function testAnonymousClass(): void
    {
        $fileName = 'AnonymousClass.phpt';

        $structure = $this->indexClass($fileName);

        $filePath = $this->normalizePath($this->getPathFor($fileName));

        self::assertSame('anonymous_' . md5($filePath) . '_19', $structure->getName());
        self::assertSame('\\anonymous_' . md5($filePath) . '_19', $structure->getFqcn());
        self::assertTrue($structure->getIsAnonymous());
    }

    /**
     * @return void
     */
    public function testClassParentChildRelationship(): void
    {
        $path = $this->getPathFor('ClassParentChildRelationship.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(2, $entities);

        self::assertCount(1, $entities[0]->getChildFqcns());
        self::assertSame($entities[1]->getFqcn(), $entities[0]->getChildFqcns()[0]);
        self::assertSame($entities[0]->getFqcn(), $entities[1]->getParentFqcn());
    }

    /**
     * @return void
     */
    public function testClassInterface(): void
    {
        $structure = $this->indexClass('ClassInterface.phpt');

        self::assertCount(1, $structure->getInterfaceFqcns());
        self::assertSame('\I', $structure->getInterfaceFqcns()[0]);
    }

    /**
     * @return void
     */
    public function testClassTrait(): void
    {
        $structure = $this->indexClass('ClassTrait.phpt');

        self::assertCount(2, $structure->getTraitFqcns());
        self::assertSame('\A', $structure->getTraitFqcns()[0]);
        self::assertSame('\B', $structure->getTraitFqcns()[1]);
    }

    /**
     * @return void
     */
    public function testClassTraitAlias(): void
    {
        $structure = $this->indexClass('ClassTraitAlias.phpt');

        self::assertCount(1, $structure->getTraitAliases());
        self::assertSame($structure, $structure->getTraitAliases()[0]->getClass());
        self::assertNull($structure->getTraitAliases()[0]->getTraitFqcn());
        self::assertNull($structure->getTraitAliases()[0]->getAccessModifier());
        self::assertSame('foo', $structure->getTraitAliases()[0]->getName());
        self::assertSame('bar', $structure->getTraitAliases()[0]->getAlias());
    }

    /**
     * @return void
     */
    public function testClassTraitAliasWithTraitName(): void
    {
        $structure = $this->indexClass('ClassTraitAliasWithTraitName.phpt');

        self::assertCount(1, $structure->getTraitAliases());
        self::assertSame('\A', $structure->getTraitAliases()[0]->getTraitFqcn());
    }

    /**
     * @return void
     */
    public function testClassTraitAliasWithAccessModifier(): void
    {
        $structure = $this->indexClass('ClassTraitAliasWithAccessModifier.phpt');

        self::assertCount(1, $structure->getTraitAliases());
        self::assertNotNull($structure->getTraitAliases()[0]->getAccessModifier());
        self::assertSame('protected', $structure->getTraitAliases()[0]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testClassTraitPrecedence(): void
    {
        $structure = $this->indexClass('ClassTraitPrecedence.phpt');

        self::assertCount(1, $structure->getTraitPrecedences());
        self::assertSame($structure, $structure->getTraitPrecedences()[0]->getClass());
        self::assertSame('\A', $structure->getTraitPrecedences()[0]->getTraitFqcn());
        self::assertSame('foo', $structure->getTraitPrecedences()[0]->getName());
    }

    /**
     * @return void
     */
    public function testClassIsCorrectlyContinuedAfterAnonymousClassStops(): void
    {
        $path = $this->getPathFor('ClassIsCorrectlyContinuedAfterAnonymousClassStops.phpt');

        $this->indexTestFile($this->container, $path);

        $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(4, $structures);

        $testClass = $structures[2];

        self::assertSame('Test', $testClass->getName());
        self::assertCount(2, $testClass->getMethods());
        self::assertSame('method1', $testClass->getMethods()[0]->getName());
        self::assertSame('method2', $testClass->getMethods()[1]->getName());
        self::assertSame('\N\Parent1', $testClass->getParentFqcn());
        self::assertSame(['\N\Trait1'], $testClass->getTraitFqcns());
        self::assertSame(['\N\Interface1'], $testClass->getInterfaceFqcns());

        $anonymousClass = $structures[3];

        self::assertCount(1, $anonymousClass->getMethods());
        self::assertSame('anonMethod', $anonymousClass->getMethods()[0]->getName());
        self::assertSame('\N\Parent2', $anonymousClass->getParentFqcn());
        self::assertSame(['\N\Trait2'], $anonymousClass->getTraitFqcns());
        self::assertSame(['\N\Interface2'], $anonymousClass->getInterfaceFqcns());
    }

    /**
     * @return void
     */
    public function testRenameChangeIsPickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $structures);

            $structure = $structures[0];

            self::assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(1, $structures);

            $structure = $structures[0];

            self::assertSame('Test2', $structure->getName());
        };

        $path = $this->getPathFor('ClassRenameChange.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testParentChangeIsPickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(3, $structures);

            $structure = $structures[2];

            self::assertSame('\Parent1', $structure->getParentFqcn());

            return str_replace('Parent1', 'Parent2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            self::assertCount(3, $structures);

            $structure = $structures[2];

            self::assertSame('\Parent2', $structure->getParentFqcn());
        };

        $path = $this->getPathFor('ClassParentChange.phpt');

        self::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @param string $file
     *
     * @return Structures\Class_
     */
    private function indexClass(string $file): Structures\Class_
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        self::assertCount(1, $entities);

        return $entities[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return 'file:///' . __DIR__ . '/ClassIndexingTest/' . $file;
    }
}
