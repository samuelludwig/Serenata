<?php

namespace Serenata\Tests\Integration\Tooltips;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Indexing\Structures;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassIndexingTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testSimpleClass(): void
    {
        $structure = $this->indexClass('SimpleClass.phpt');

        static::assertSame('Test', $structure->getName());
        static::assertSame('\Test', $structure->getFqcn());
        static::assertSame($this->getPathFor('SimpleClass.phpt'), $structure->getFile()->getPath());
        static::assertEquals(
            new Range(
                new Position(2, 0),
                new Position(5, 1)
            ),
            $structure->getRange()
        );
        static::assertNull($structure->getShortDescription());
        static::assertNull($structure->getLongDescription());
        static::assertFalse($structure->getIsDeprecated());
        static::assertFalse($structure->getHasDocblock());
        static::assertCount(1, $structure->getConstants());
        static::assertEmpty($structure->getProperties());
        static::assertEmpty($structure->getMethods());
        static::assertFalse($structure->getIsAbstract());
        static::assertFalse($structure->getIsFinal());
        static::assertFalse($structure->getIsAnnotation());
        static::assertNull($structure->getParentFqcn());
        static::assertEmpty($structure->getChildFqcns());
        static::assertEmpty($structure->getInterfaceFqcns());
        static::assertEmpty($structure->getTraitFqcns());
        static::assertEmpty($structure->getTraitAliases());
        static::assertEmpty($structure->getTraitPrecedences());
    }

    /**
     * @return void
     */
    public function testClassNamespace(): void
    {
        $structure = $this->indexClass('ClassNamespace.phpt');

        static::assertSame('\N\Test', $structure->getFqcn());
    }

    /**
     * @return void
     */
    public function testClassShortDescription(): void
    {
        $structure = $this->indexClass('ClassShortDescription.phpt');

        static::assertSame('A summary.', $structure->getShortDescription());
    }

    /**
     * @return void
     */
    public function testClassLongDescription(): void
    {
        $structure = $this->indexClass('ClassLongDescription.phpt');

        static::assertSame('A long description.', $structure->getLongDescription());
    }

    /**
     * @return void
     */
    public function testDeprecatedClass(): void
    {
        $structure = $this->indexClass('DeprecatedClass.phpt');

        static::assertTrue($structure->getIsDeprecated());
    }

    /**
     * @return void
     */
    public function testClassWithDocblock(): void
    {
        $structure = $this->indexClass('ClassWithDocblock.phpt');

        static::assertTrue($structure->getHasDocblock());
    }

    /**
     * @return void
     */
    public function testAbstractClass(): void
    {
        $structure = $this->indexClass('AbstractClass.phpt');

        static::assertTrue($structure->getIsAbstract());
    }

    /**
     * @return void
     */
    public function testFinalClass(): void
    {
        $structure = $this->indexClass('FinalClass.phpt');

        static::assertTrue($structure->getIsFinal());
    }

    /**
     * @return void
     */
    public function testAnnotationClass(): void
    {
        $structure = $this->indexClass('AnnotationClass.phpt');

        static::assertTrue($structure->getIsAnnotation());
    }

    /**
     * @return void
     */
    public function testAnonymousClass(): void
    {
        $fileName = 'AnonymousClass.phpt';

        $structure = $this->indexClass($fileName);

        $filePath = $this->getPathFor($fileName);

        static::assertSame('(anonymous_' . md5($filePath) . '_19)', $structure->getName());
        static::assertSame('\\(anonymous_' . md5($filePath) . '_19)', $structure->getFqcn());
        static::assertTrue($structure->getIsAnonymous());
    }

    /**
     * @return void
     */
    public function testClassParentChildRelationship(): void
    {
        $path = $this->getPathFor('ClassParentChildRelationship.phpt');

        $this->indexTestFile($this->container, $path);

        $entities = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(2, $entities);

        static::assertCount(1, $entities[0]->getChildFqcns());
        static::assertSame($entities[1]->getFqcn(), $entities[0]->getChildFqcns()[0]);
        static::assertSame($entities[0]->getFqcn(), $entities[1]->getParentFqcn());
    }

    /**
     * @return void
     */
    public function testClassInterface(): void
    {
        $structure = $this->indexClass('ClassInterface.phpt');

        static::assertCount(1, $structure->getInterfaceFqcns());
        static::assertSame('\I', $structure->getInterfaceFqcns()[0]);
    }

    /**
     * @return void
     */
    public function testClassTrait(): void
    {
        $structure = $this->indexClass('ClassTrait.phpt');

        static::assertCount(2, $structure->getTraitFqcns());
        static::assertSame('\A', $structure->getTraitFqcns()[0]);
        static::assertSame('\B', $structure->getTraitFqcns()[1]);
    }

    /**
     * @return void
     */
    public function testClassTraitAlias(): void
    {
        $structure = $this->indexClass('ClassTraitAlias.phpt');

        static::assertCount(1, $structure->getTraitAliases());
        static::assertSame($structure, $structure->getTraitAliases()[0]->getClass());
        static::assertNull($structure->getTraitAliases()[0]->getTraitFqcn());
        static::assertNull($structure->getTraitAliases()[0]->getAccessModifier());
        static::assertSame('foo', $structure->getTraitAliases()[0]->getName());
        static::assertSame('bar', $structure->getTraitAliases()[0]->getAlias());
    }

    /**
     * @return void
     */
    public function testClassTraitAliasWithTraitName(): void
    {
        $structure = $this->indexClass('ClassTraitAliasWithTraitName.phpt');

        static::assertCount(1, $structure->getTraitAliases());
        static::assertSame('\A', $structure->getTraitAliases()[0]->getTraitFqcn());
    }

    /**
     * @return void
     */
    public function testClassTraitAliasWithAccessModifier(): void
    {
        $structure = $this->indexClass('ClassTraitAliasWithAccessModifier.phpt');

        static::assertCount(1, $structure->getTraitAliases());
        static::assertNotNull($structure->getTraitAliases()[0]->getAccessModifier());
        static::assertSame('protected', $structure->getTraitAliases()[0]->getAccessModifier()->getName());
    }

    /**
     * @return void
     */
    public function testClassTraitPrecedence(): void
    {
        $structure = $this->indexClass('ClassTraitPrecedence.phpt');

        static::assertCount(1, $structure->getTraitPrecedences());
        static::assertSame($structure, $structure->getTraitPrecedences()[0]->getClass());
        static::assertSame('\A', $structure->getTraitPrecedences()[0]->getTraitFqcn());
        static::assertSame('foo', $structure->getTraitPrecedences()[0]->getName());
    }

    /**
     * @return void
     */
    public function testClassIsCorrectlyContinuedAfterAnonymousClassStops(): void
    {
        $path = $this->getPathFor('ClassIsCorrectlyContinuedAfterAnonymousClassStops.phpt');

        $this->indexTestFile($this->container, $path);

        $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

        static::assertCount(4, $structures);

        $testClass = $structures[2];

        static::assertSame('Test', $testClass->getName());
        static::assertCount(2, $testClass->getMethods());
        static::assertSame('method1', $testClass->getMethods()[0]->getName());
        static::assertSame('method2', $testClass->getMethods()[1]->getName());
        static::assertSame('\N\Parent1', $testClass->getParentFqcn());
        static::assertSame(['\N\Trait1'], $testClass->getTraitFqcns());
        static::assertSame(['\N\Interface1'], $testClass->getInterfaceFqcns());

        $anonymousClass = $structures[3];

        static::assertCount(1, $anonymousClass->getMethods());
        static::assertSame('anonMethod', $anonymousClass->getMethods()[0]->getName());
        static::assertSame('\N\Parent2', $anonymousClass->getParentFqcn());
        static::assertSame(['\N\Trait2'], $anonymousClass->getTraitFqcns());
        static::assertSame(['\N\Interface2'], $anonymousClass->getInterfaceFqcns());
    }

    /**
     * @return void
     */
    public function testRenameChangeIsPickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $structures);

            $structure = $structures[0];

            static::assertSame('Test', $structure->getName());

            return str_replace('Test', 'Test2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(1, $structures);

            $structure = $structures[0];

            static::assertSame('Test2', $structure->getName());
        };

        $path = $this->getPathFor('ClassRenameChange.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
    }

    /**
     * @return void
     */
    public function testParentChangeIsPickedUpOnReindex(): void
    {
        $afterIndex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(3, $structures);

            $structure = $structures[2];

            static::assertSame('\Parent1', $structure->getParentFqcn());

            return str_replace('Parent1', 'Parent2 ', $source);
        };

        $afterReindex = function (ContainerBuilder $container, string $path, string $source) {
            $structures = $this->container->get('managerRegistry')->getRepository(Structures\Class_::class)->findAll();

            static::assertCount(3, $structures);

            $structure = $structures[2];

            static::assertSame('\Parent2', $structure->getParentFqcn());
        };

        $path = $this->getPathFor('ClassParentChange.phpt');

        static::assertReindexingChanges($path, $afterIndex, $afterReindex);
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

        static::assertCount(1, $entities);

        return $entities[0];
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/ClassIndexingTest/' . $file;
    }
}
