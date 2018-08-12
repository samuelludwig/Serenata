<?php

namespace Serenata\Tests\Integration\UserInterface\Command;

use Serenata\Common\Range;
use Serenata\Common\Position;

use Serenata\Tests\Integration\AbstractIntegrationTest;

class ClassInfoCommandTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testLeadingSlashIsResolvedCorrectly(): void
    {
        $fileName = 'SimpleClass.phpt';

        static::assertSame(
            $this->getClassInfo($fileName, 'A\SimpleClass'),
            $this->getClassInfo($fileName, '\A\SimpleClass')
        );
    }

    /**
     * @return void
     */
    public function testDataIsCorrectForASimpleClass(): void
    {
        $fileName = 'SimpleClass.phpt';

        $output = $this->getClassInfo($fileName, 'A\SimpleClass');

        static::assertSame([
            'name'               => 'SimpleClass',
            'fqcn'               => '\A\SimpleClass',
            'range'              => $output['range'],
            'filename'           => $this->getPathFor($fileName),
            'type'               => 'class',
            'isDeprecated'       => false,
            'hasDocblock'        => true,
            'hasDocumentation'   => true,
            'shortDescription'   => 'This is the summary.',
            'longDescription'    => 'This is a long description.',
            'isAnonymous'        => false,
            'isAbstract'         => false,
            'isFinal'            => false,
            'isAnnotation'       => false,
            'parents'            => [],
            'interfaces'         => [],
            'traits'             => [],
            'directParents'      => [],
            'directInterfaces'   => [],
            'directTraits'       => [],
            'directChildren'     => [],
            'directImplementors' => [],
            'directTraitUsers'   => [],
            'constants'          => [
                'class' => [
                    'name'               => 'class',
                    'range'              => $output['constants']['class']['range'],
                    'defaultValue'       => '\'A\SimpleClass\'',
                    'filename'           => $this->getPathFor($fileName),
                    'isStatic'           => true,
                    'isDeprecated'       => false,
                    'hasDocblock'        => false,
                    'hasDocumentation'   => false,
                    'shortDescription'   => 'PHP built-in class constant that evaluates to the FQCN.',
                    'longDescription'    => null,
                    'typeDescription'    => null,

                    'types'             => [
                        [
                            'type'         => 'string',
                            'resolvedType' => 'string',
                        ],
                    ],

                    'isPublic'           => true,
                    'isProtected'        => false,
                    'isPrivate'          => false,

                    'declaringClass'     => [
                        'fqcn'      => '\A\SimpleClass',
                        'filename'  => $this->getPathFor($fileName),
                        'range'     => $output['constants']['class']['declaringClass']['range'],
                        'type'      => 'class',
                    ],

                    'declaringStructure' => [
                        'fqcn'            => '\A\SimpleClass',
                        'filename'        => $this->getPathFor($fileName),
                        'range'           => $output['constants']['class']['declaringStructure']['range'],
                        'type'            => 'class',
                        'memberRange'     => $output['constants']['class']['declaringStructure']['memberRange'],
                    ],
                ],
            ],
            'properties'         => [],
            'methods'            => [],
        ], $output);

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(9, 0)
            ),
            $output['constants']['class']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['constants']['class']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['constants']['class']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(9, 0)
            ),
            $output['constants']['class']['declaringStructure']['memberRange']
        );

        static::assertEquals(new Range(new Position(9, 0), new Position(12, 1)), $output['range']);
    }

    /**
     * @return void
     */
    public function testDataIsCorrectForClassProperties(): void
    {
        $fileName = 'ClassProperty.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame([
            'name'               => 'testProperty',
            'range'              => $output['properties']['testProperty']['range'],
            'filename'           => $this->getPathFor($fileName),
            'defaultValue'       => "'test'",
            'isMagic'            => false,
            'isPublic'           => false,
            'isProtected'        => true,
            'isPrivate'          => false,
            'isStatic'           => false,
            'isDeprecated'       => false,
            'hasDocblock'        => true,
            'hasDocumentation'   => true,
            'shortDescription'   => 'This is the summary.',
            'longDescription'    => 'This is a long description.',
            'typeDescription'    => null,

            'types'             => [
                [
                    'type'         => '\A\MyType',
                    'resolvedType' => '\A\MyType',
                ],

                [
                    'type'         => 'string',
                    'resolvedType' => 'string',
                ],
            ],

            'override'           => null,

            'declaringClass' => [
                'fqcn'      => '\A\TestClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['properties']['testProperty']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\TestClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['properties']['testProperty']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['properties']['testProperty']['declaringStructure']['memberRange'],
            ],
        ], $output['properties']['testProperty']);

        static::assertEquals(
            new Range(
                new Position(13, 14),
                new Position(13, 36)
            ),
            $output['properties']['testProperty']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(14, 1)
            ),
            $output['properties']['testProperty']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(14, 1)
            ),
            $output['properties']['testProperty']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(13, 14),
                new Position(13, 36)
            ),
            $output['properties']['testProperty']['declaringStructure']['memberRange']
        );
    }

    /**
     * @return void
     */
    public function testDataIsCorrectForClassMethods(): void
    {
        $fileName = 'ClassMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame([
            'name'               => 'testMethod',
            'range'              => $output['methods']['testMethod']['range'],
            'filename'           => $this->getPathFor($fileName),

            'parameters'         => [
                [
                    'name'         => 'firstParameter',
                    'typeHint'     => '\DateTimeInterface',
                    'types'        => [
                        [
                            'type'         => '\DateTimeInterface',
                            'resolvedType' => '\DateTimeInterface',
                        ],

                        [
                            'type'         => '\DateTime',
                            'resolvedType' => '\DateTime',
                        ],
                    ],

                    'description'  => 'First parameter description.',
                    'defaultValue' => 'null',
                    'isReference'  => false,
                    'isVariadic'   => false,
                    'isOptional'   => true,
                ],

                [
                    'name'         => 'secondParameter',
                    'typeHint'     => null,
                    'types'        => [
                        [
                            'type'         => 'bool',
                            'resolvedType' => 'bool',
                        ],
                    ],

                    'description'  => null,
                    'defaultValue' => 'true',
                    'isReference'  => true,
                    'isVariadic'   => false,
                    'isOptional'   => true,
                ],

                [
                    'name'         => 'thirdParameter',
                    'typeHint'     => null,
                    'types'        => [
                        [
                            'type'         => 'mixed[]',
                            'resolvedType' => 'mixed[]',
                        ],
                    ],
                    'description'  => null,
                    'defaultValue' => null,
                    'isReference'  => false,
                    'isVariadic'   => true,
                    'isOptional'   => false,
                ],
            ],

            'throws'             => [
                [
                    'type'        => '\UnexpectedValueException',
                    'description' => 'when something goes wrong.',
                ],

                [
                    'type'        => '\LogicException',
                    'description' => 'when something is wrong.',
                ],
            ],

            'isDeprecated'       => false,
            'hasDocblock'        => true,
            'hasDocumentation'   => true,

            'shortDescription'   => 'This is the summary.',
            'longDescription'    => 'This is a long description.',
            'returnDescription'  => null,
            'returnTypeHint'     => null,

            'returnTypes' => [
                [
                    'type'         => 'mixed',
                    'resolvedType' => 'mixed',
                ],

                [
                    'type'         => 'bool',
                    'resolvedType' => 'bool',
                ],
            ],

            'isMagic'            => false,
            'isPublic'           => true,
            'isProtected'        => false,
            'isPrivate'          => false,
            'isStatic'           => false,
            'isAbstract'         => false,
            'isFinal'            => false,
            'override'           => null,
            'implementations'    => [],

            'declaringClass'     => [
                'fqcn'      => '\A\TestClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['methods']['testMethod']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\TestClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['testMethod']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['methods']['testMethod']['declaringStructure']['memberRange'],
            ],
        ], $output['methods']['testMethod']);

        static::assertEquals(
            new Range(
                new Position(18, 4),
                new Position(21, 5)
            ),
            $output['methods']['testMethod']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(22, 1)
            ),
            $output['methods']['testMethod']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(22, 1)
            ),
            $output['methods']['testMethod']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(18, 4),
                new Position(21, 5)
            ),
            $output['methods']['testMethod']['declaringStructure']['memberRange']
        );
    }

    /**
     * @return void
     */
    public function testDataIsCorrectForClassConstants(): void
    {
        $fileName = 'ClassConstant.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame([
            'name'               => 'TEST_CONSTANT',
            'range'              => $output['constants']['TEST_CONSTANT']['range'],
            'defaultValue'       => '5',
            'filename'           => $this->getPathFor($fileName),
            'isStatic'           => true,
            'isDeprecated'       => false,
            'hasDocblock'        => true,
            'hasDocumentation'   => true,
            'shortDescription'   => 'This is the summary.',
            'longDescription'    => 'This is a long description.',
            'typeDescription'    => null,

            'types'             => [
                [
                    'type'         => '\A\MyType',
                    'resolvedType' => '\A\MyType',
                ],

                [
                    'type'         => 'string',
                    'resolvedType' => 'string',
                ],
            ],

            'isPublic'           => true,
            'isProtected'        => false,
            'isPrivate'          => false,

            'declaringClass'     => [
                'fqcn'      => '\A\TestClass',
                'filename'  => $this->getPathFor($fileName),
                'range'           => $output['constants']['TEST_CONSTANT']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\TestClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['constants']['TEST_CONSTANT']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['constants']['TEST_CONSTANT']['declaringStructure']['memberRange'],
            ],
        ], $output['constants']['TEST_CONSTANT']);

        static::assertEquals(
            new Range(
                new Position(13, 10),
                new Position(13, 27)
            ),
            $output['constants']['TEST_CONSTANT']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(14, 1)
            ),
            $output['constants']['TEST_CONSTANT']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(14, 1)
            ),
            $output['constants']['TEST_CONSTANT']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(13, 10),
                new Position(13, 27)
            ),
            $output['constants']['TEST_CONSTANT']['declaringStructure']['memberRange']
        );
    }

    /**
     * @return void
     */
    public function testDocblockInheritanceWorksProperlyForClasses(): void
    {
        $fileName = 'ClassDocblockInheritance.phpt';

        $childClassOutput = $this->getClassInfo($fileName, 'A\ChildClass');
        $parentClassOutput = $this->getClassInfo($fileName, 'A\ParentClass');
        $anotherChildClassOutput = $this->getClassInfo($fileName, 'A\AnotherChildClass');

        static::assertSame('This is the summary.', $childClassOutput['shortDescription']);
        static::assertSame('This is a long description.', $childClassOutput['longDescription']);

        static::assertSame(
            'Pre. ' . $parentClassOutput['longDescription'] . ' Post.',
            $anotherChildClassOutput['longDescription']
        );
    }

    /**
     * @return void
     */
    public function testDocblockInheritanceWorksProperlyForMethods(): void
    {
        $fileName = 'MethodDocblockInheritance.phpt';

        $traitOutput       = $this->getClassInfo($fileName, 'A\TestTrait');
        $interfaceOutput   = $this->getClassInfo($fileName, 'A\TestInterface');
        $childClassOutput  = $this->getClassInfo($fileName, 'A\ChildClass');
        $parentClassOutput = $this->getClassInfo($fileName, 'A\ParentClass');

        $keysToTestForEquality = [
            'hasDocumentation',
            'isDeprecated',
            'longDescription',
            'shortDescription',
            'returnTypes',
            'parameters',
            'throws',
        ];

        foreach ($keysToTestForEquality as $key) {
            static::assertSame(
                $childClassOutput['methods']['basicDocblockInheritanceTraitTest'][$key],
                $traitOutput['methods']['basicDocblockInheritanceTraitTest'][$key]
            );

            static::assertSame(
                $childClassOutput['methods']['basicDocblockInheritanceInterfaceTest'][$key],
                $interfaceOutput['methods']['basicDocblockInheritanceInterfaceTest'][$key]
            );

            static::assertSame(
                $childClassOutput['methods']['basicDocblockInheritanceBaseClassTest'][$key],
                $parentClassOutput['methods']['basicDocblockInheritanceBaseClassTest'][$key]
            );
        }

        static::assertSame(
            'Pre. ' . $parentClassOutput['methods']['inheritDocBaseClassTest']['longDescription'] . ' Post.',
            $childClassOutput['methods']['inheritDocBaseClassTest']['longDescription']
        );

        static::assertSame(
            'Pre. ' . $interfaceOutput['methods']['inheritDocInterfaceTest']['longDescription'] . ' Post.',
            $childClassOutput['methods']['inheritDocInterfaceTest']['longDescription']
        );

        static::assertSame(
            'Pre. ' . $traitOutput['methods']['inheritDocTraitTest']['longDescription'] . ' Post.',
            $childClassOutput['methods']['inheritDocTraitTest']['longDescription']
        );
    }

    /**
     * @return void
     */
    public function testDocblockInheritanceWorksProperlyForProperties(): void
    {
        $fileName = 'PropertyDocblockInheritance.phpt';

        $traitOutput       = $this->getClassInfo($fileName, 'A\TestTrait');
        $childClassOutput  = $this->getClassInfo($fileName, 'A\ChildClass');
        $parentClassOutput = $this->getClassInfo($fileName, 'A\ParentClass');

        $keysToTestForEquality = [
            'hasDocumentation',
            'isDeprecated',
            'shortDescription',
            'longDescription',
            'typeDescription',
            'types',
        ];

        foreach ($keysToTestForEquality as $key) {
            static::assertSame(
                $childClassOutput['properties']['basicDocblockInheritanceTraitTest'][$key],
                $traitOutput['properties']['basicDocblockInheritanceTraitTest'][$key]
            );

            static::assertSame(
                $childClassOutput['properties']['basicDocblockInheritanceBaseClassTest'][$key],
                $parentClassOutput['properties']['basicDocblockInheritanceBaseClassTest'][$key]
            );
        }

        static::assertSame(
            $childClassOutput['properties']['inheritDocBaseClassTest']['longDescription'],
            'Pre. ' . $parentClassOutput['properties']['inheritDocBaseClassTest']['longDescription'] . ' Post.'
        );

        static::assertSame(
            $childClassOutput['properties']['inheritDocTraitTest']['longDescription'],
            'Pre. ' . $traitOutput['properties']['inheritDocTraitTest']['longDescription'] . ' Post.'
        );
    }

    /**
     * @return void
     */
    public function testMethodOverridingIsAnalyzedCorrectly(): void
    {
        $fileName = 'MethodOverride.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            [
                'name'         => 'foo',
                'typeHint'     => '\A\Foo',
                'types' => [
                    [
                        'type'         => '\A\Foo',
                        'resolvedType' => '\A\Foo',
                    ],
                ],

                'description'  => null,
                'defaultValue' => null,
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => false,
            ],
        ], $output['methods']['__construct']['parameters']);

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['methods']['__construct']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['__construct']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['methods']['__construct']['override']['declaringStructure']['memberRange'],
            ],

            'range'       => $output['methods']['__construct']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['__construct']['override']);

        static::assertEquals(
            new Range(
                new Position(24, 4),
                new Position(27, 5)
            ),
            $output['methods']['__construct']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['__construct']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['__construct']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(24, 4),
                new Position(27, 5)
            ),
            $output['methods']['__construct']['override']['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(54, 4),
                new Position(57, 5)
            ),
            $output['methods']['__construct']['range']
        );

        static::assertSame([
            [
                'name'         => 'foo',
                'typeHint'     => '\A\Foo',
                'types' => [
                    [
                        'type'         => '\A\Foo',
                        'resolvedType' => '\A\Foo',
                    ],

                    [
                        'type'         => 'null',
                        'resolvedType' => 'null',
                    ],
                ],

                'description'  => null,
                'defaultValue' => 'null',
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => true,
            ],
        ], $output['methods']['parentTraitMethod']['parameters']);

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['methods']['parentTraitMethod']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentTrait',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['parentTraitMethod']['override']['declaringStructure']['range'],
                'type'            => 'trait',
                'memberRange'     => $output['methods']['parentTraitMethod']['override']['declaringStructure']['memberRange'],
            ],

            'range'       => $output['methods']['parentTraitMethod']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['parentTraitMethod']['override']);

        static::assertEquals(
            new Range(
                new Position(14, 4),
                new Position(17, 5)
            ),
            $output['methods']['parentTraitMethod']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['parentTraitMethod']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(12, 0),
                new Position(18, 1)
            ),
            $output['methods']['parentTraitMethod']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(14, 4),
                new Position(17, 5)
            ),
            $output['methods']['parentTraitMethod']['override']['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(64, 4),
                new Position(67, 5)
            ),
            $output['methods']['parentTraitMethod']['range']
        );

        static::assertSame([
            [
                'name'         => 'foo',
                'typeHint'     => '\A\Foo',

                'types' => [
                    [
                        'type'         => '\A\Foo',
                        'resolvedType' => '\A\Foo',
                    ],

                    [
                        'type'         => 'null',
                        'resolvedType' => 'null',
                    ],
                ],

                'description'  => null,
                'defaultValue' => 'null',
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => true,
            ],
        ], $output['methods']['parentMethod']['parameters']);

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['methods']['parentMethod']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['parentMethod']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['methods']['parentMethod']['override']['declaringStructure']['memberRange'],
            ],

            'range'     => $output['methods']['parentMethod']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['parentMethod']['override']);

        static::assertEquals(
            new Range(
                new Position(29, 4),
                new Position(32, 5)
            ),
            $output['methods']['parentMethod']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['parentMethod']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['parentMethod']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(29, 4),
                new Position(32, 5)
            ),
            $output['methods']['parentMethod']['override']['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(69, 4),
                new Position(72, 5)
            ),
            $output['methods']['parentMethod']['range']
        );

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['methods']['ancestorMethod']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['ancestorMethod']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['methods']['ancestorMethod']['override']['declaringStructure']['memberRange'],
            ],

            'range'       => $output['methods']['ancestorMethod']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['ancestorMethod']['override']);

        static::assertEquals(
            new Range(
                new Position(34, 4),
                new Position(37, 5)
            ),
            $output['methods']['ancestorMethod']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['ancestorMethod']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(20, 0),
                new Position(38, 1)
            ),
            $output['methods']['ancestorMethod']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(34, 4),
                new Position(37, 5)
            ),
            $output['methods']['ancestorMethod']['override']['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(59, 4),
                new Position(62, 5)
            ),
            $output['methods']['ancestorMethod']['range']
        );

        static::assertSame([
            [
                'name'         => 'foo',
                'typeHint'     => '\A\Foo',

                'types' => [
                    [
                        'type'         => '\A\Foo',
                        'resolvedType' => '\A\Foo',
                    ],

                    [
                        'type'         => 'null',
                        'resolvedType' => 'null',
                    ],
                ],

                'description'  => null,
                'defaultValue' => 'null',
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => true,
            ],
        ], $output['methods']['traitMethod']['parameters']);

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\TestTrait',
                'filename'  =>  $this->getPathFor($fileName),
                'range'     => $output['methods']['traitMethod']['override']['declaringClass']['range'],
                'type'      => 'trait',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\TestTrait',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['traitMethod']['override']['declaringStructure']['range'],
                'type'            => 'trait',
                'memberRange'     => $output['methods']['traitMethod']['override']['declaringStructure']['memberRange'],
            ],

            'range'       => $output['methods']['traitMethod']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['traitMethod']['override']);

        static::assertEquals(
            new Range(
                new Position(42, 4),
                new Position(45, 5)
            ),
            $output['methods']['traitMethod']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(40, 0),
                new Position(48, 1)
            ),
            $output['methods']['traitMethod']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(40, 0),
                new Position(48, 1)
            ),
            $output['methods']['traitMethod']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(42, 4),
                new Position(45, 5)
            ),
            $output['methods']['traitMethod']['override']['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(74, 4),
                new Position(77, 5)
            ),
            $output['methods']['traitMethod']['range']
        );

        static::assertSame([
            [
                'name'         => 'foo',
                'typeHint'     => '\A\Foo',

                'types' => [
                    [
                        'type'         => '\A\Foo',
                        'resolvedType' => '\A\Foo',
                    ],

                    [
                        'type'         => 'null',
                        'resolvedType' => 'null',
                    ],
                ],

                'description'  => null,
                'defaultValue' => 'null',
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => true,
            ],
        ], $output['methods']['abstractMethod']['parameters']);

        static::assertSame($output['methods']['abstractMethod']['override']['wasAbstract'], true);
    }

    /**
     * @return void
     */
    public function testMethodOverridingOfParentImplementationIsAnalyzedCorrectly(): void
    {
        $fileName = 'MethodOverrideOfParentImplementation.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  =>  $this->getPathFor($fileName),
                'range'     => $output['methods']['interfaceMethod']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['interfaceMethod']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['methods']['interfaceMethod']['override']['declaringStructure']['memberRange'],
            ],

            'range'       => $output['methods']['interfaceMethod']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['interfaceMethod']['override']);

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(14, 5)
            ),
            $output['methods']['interfaceMethod']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(15, 1)
            ),
            $output['methods']['interfaceMethod']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(15, 1)
            ),
            $output['methods']['interfaceMethod']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(14, 5)
            ),
            $output['methods']['interfaceMethod']['override']['declaringStructure']['memberRange']
        );

        static::assertEmpty($output['methods']['interfaceMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(19, 4),
                new Position(22, 5)
            ),
            $output['methods']['interfaceMethod']['range']
        );
    }

    /**
     * @return void
     */
    public function testMethodOverridingAndImplementationSimultaneouslyIsAnalyzedCorrectly(): void
    {
        $fileName = 'MethodOverrideAndImplementation.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface',
                    'filename'  =>  $this->getPathFor($fileName),
                    'range'     => $output['methods']['interfaceMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['interfaceMethod']['implementations'][0]['range'],
            ],
        ], $output['methods']['interfaceMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 38)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 38)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  =>  $this->getPathFor($fileName),
                'range'     => $output['methods']['interfaceMethod']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['methods']['interfaceMethod']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['methods']['interfaceMethod']['override']['declaringStructure']['memberRange'],
            ],

            'range'       => $output['methods']['interfaceMethod']['override']['range'],
            'wasAbstract' => false,
        ], $output['methods']['interfaceMethod']['override']);

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(14, 5)
            ),
            $output['methods']['interfaceMethod']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(15, 1)
            ),
            $output['methods']['interfaceMethod']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(15, 1)
            ),
            $output['methods']['interfaceMethod']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(14, 5)
            ),
            $output['methods']['interfaceMethod']['override']['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(19, 4),
                new Position(22, 5)
            ),
            $output['methods']['interfaceMethod']['range']
        );
    }

    /**
     * @return void
     */
    public function testPropertyOverridingIsAnalyzedCorrectly(): void
    {
        $fileName = 'PropertyOverride.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['properties']['parentTraitProperty']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentTrait',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['properties']['parentTraitProperty']['override']['declaringStructure']['range'],
                'type'            => 'trait',
                'memberRange'     => $output['properties']['parentTraitProperty']['override']['declaringStructure']['memberRange'],
            ],

            'range' => $output['properties']['parentTraitProperty']['override']['range'],
        ], $output['properties']['parentTraitProperty']['override']);

        static::assertEquals(
            new Range(
                new Position(11, 14),
                new Position(11, 34)
            ),
            $output['properties']['parentTraitProperty']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(14, 0),
                new Position(20, 1)
            ),
            $output['properties']['parentTraitProperty']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['properties']['parentTraitProperty']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(11, 14),
                new Position(11, 34)
            ),
            $output['properties']['parentTraitProperty']['override']['declaringStructure']['memberRange']
        );

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['properties']['parentProperty']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['properties']['parentProperty']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['properties']['parentProperty']['override']['declaringStructure']['memberRange'],
            ],

            'range' => $output['properties']['parentProperty']['override']['range'],
        ], $output['properties']['parentProperty']['override']);

        static::assertEquals(
            new Range(
                new Position(18, 14),
                new Position(18, 29)
            ),
            $output['properties']['parentProperty']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(14, 0),
                new Position(20, 1)
            ),
            $output['properties']['parentProperty']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(14, 0),
                new Position(20, 1)
            ),
            $output['properties']['parentProperty']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(18, 14),
                new Position(18, 29)
            ),
            $output['properties']['parentProperty']['override']['declaringStructure']['memberRange']
        );

        static::assertSame([
            'declaringClass' => [
                'fqcn'      => '\A\ParentClass',
                'filename'  => $this->getPathFor($fileName),
                'range'     => $output['properties']['ancestorProperty']['override']['declaringClass']['range'],
                'type'      => 'class',
            ],

            'declaringStructure' => [
                'fqcn'            => '\A\ParentClass',
                'filename'        => $this->getPathFor($fileName),
                'range'           => $output['properties']['ancestorProperty']['override']['declaringStructure']['range'],
                'type'            => 'class',
                'memberRange'     => $output['properties']['ancestorProperty']['override']['declaringStructure']['memberRange'],
            ],

            'range' => $output['properties']['ancestorProperty']['override']['range'],
        ], $output['properties']['ancestorProperty']['override']);

        static::assertEquals(
            new Range(
                new Position(19, 14),
                new Position(19, 31)
            ),
            $output['properties']['ancestorProperty']['override']['range']
        );

        static::assertEquals(
            new Range(
                new Position(14, 0),
                new Position(20, 1)
            ),
            $output['properties']['ancestorProperty']['override']['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(14, 0),
                new Position(20, 1)
            ),
            $output['properties']['ancestorProperty']['override']['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(19, 14),
                new Position(19, 31)
            ),
            $output['properties']['ancestorProperty']['override']['declaringStructure']['memberRange']
        );
    }

    /**
     * @return void
     */
    public function testMethodImplementationIsAnalyzedCorrectlyWhenImplementingMethodFromInterfaceReferencedByParentClass(): void
    {
        $fileName = 'MethodImplementationFromParentClassInterface.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            [
                'name'         => 'foo',
                'typeHint'     => '\A\Foo',
                'types' => [
                    [
                        'type'         => '\A\Foo',
                        'resolvedType' => '\A\Foo',
                    ],

                    [
                        'type'         => 'null',
                        'resolvedType' => 'null',
                    ],
                ],

                'description'  => null,
                'defaultValue' => 'null',
                'isReference'  => false,
                'isVariadic'   => false,
                'isOptional'   => true,
            ],
        ], $output['methods']['parentInterfaceMethod']['parameters']);

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\ParentClass',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['parentInterfaceMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'class',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\ParentInterface',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['parentInterfaceMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['parentInterfaceMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['parentInterfaceMethod']['implementations'][0]['range'],
            ],
        ], $output['methods']['parentInterfaceMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 44)
            ),
            $output['methods']['parentInterfaceMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['parentInterfaceMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['parentInterfaceMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 44)
            ),
            $output['methods']['parentInterfaceMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertSame('\A\ChildClass', $output['methods']['parentInterfaceMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\ChildClass', $output['methods']['parentInterfaceMethod']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationIsAnalyzedCorrectlyWhenImplementingMethodFromInterfaceParent(): void
    {
        $fileName = 'MethodImplementationFromInterfaceParent.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\ParentInterface',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['interfaceParentMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\ParentInterface',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['interfaceParentMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['interfaceParentMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['interfaceParentMethod']['implementations'][0]['range'],
            ],
        ], $output['methods']['interfaceParentMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 44)
            ),
            $output['methods']['interfaceParentMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['interfaceParentMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['interfaceParentMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 44)
            ),
            $output['methods']['interfaceParentMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertNull($output['methods']['interfaceParentMethod']['override']);

        static::assertSame('\A\ChildClass', $output['methods']['interfaceParentMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\ChildClass', $output['methods']['interfaceParentMethod']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationIsAnalyzedCorrectlyWhenImplementingMethodFromInterfaceDirectlyReferenced(): void
    {
        $fileName = 'MethodImplementationFromDirectInterface.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['interfaceMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['interfaceMethod']['implementations'][0]['range'],
            ],
        ], $output['methods']['interfaceMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 38)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 38)
            ),
            $output['methods']['interfaceMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertSame('\A\ChildClass', $output['methods']['interfaceMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\ChildClass', $output['methods']['interfaceMethod']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testMethodParameterTypeIsCorrectlyDeducedIfParameterIsVariadic(): void
    {
        $fileName = 'MethodVariadicParameter.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');
        $parameters = $output['methods']['testMethod']['parameters'];

        static::assertSame('\stdClass[]', $parameters[0]['types'][0]['type']);
    }

    /**
     * @return void
     */
    public function testDataIsCorrectForClassInheritance(): void
    {
        $fileName = 'ClassInheritance.phpt';

        $output = $this->getClassInfo($fileName, 'A\ChildClass');

        static::assertSame($output['parents'], ['\A\BaseClass', '\A\AncestorClass']);
        static::assertSame($output['directParents'], ['\A\BaseClass']);

        static::assertThat($output['constants'], $this->arrayHasKey('INHERITED_CONSTANT'));
        static::assertThat($output['constants'], $this->arrayHasKey('CHILD_CONSTANT'));

        static::assertThat($output['properties'], $this->arrayHasKey('inheritedProperty'));
        static::assertThat($output['properties'], $this->arrayHasKey('childProperty'));

        static::assertThat($output['methods'], $this->arrayHasKey('inheritedMethod'));
        static::assertThat($output['methods'], $this->arrayHasKey('childMethod'));

        // Do a couple of sanity checks.
        static::assertSame('\A\BaseClass', $output['constants']['INHERITED_CONSTANT']['declaringClass']['fqcn']);
        static::assertSame('\A\BaseClass', $output['properties']['inheritedProperty']['declaringClass']['fqcn']);
        static::assertSame('\A\BaseClass', $output['methods']['inheritedMethod']['declaringClass']['fqcn']);

        static::assertSame('\A\BaseClass', $output['constants']['INHERITED_CONSTANT']['declaringStructure']['fqcn']);
        static::assertSame('\A\BaseClass', $output['properties']['inheritedProperty']['declaringStructure']['fqcn']);
        static::assertSame('\A\BaseClass', $output['methods']['inheritedMethod']['declaringStructure']['fqcn']);

        $output = $this->getClassInfo($fileName, 'A\BaseClass');

        static::assertSame($output['directChildren'], ['\A\ChildClass']);
        static::assertSame($output['parents'], ['\A\AncestorClass']);
    }

    /**
     * @return void
     */
    public function testInterfaceImplementationIsCorrectlyProcessed(): void
    {
        $fileName = 'InterfaceImplementation.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame(['\A\BaseInterface', '\A\FirstInterface', '\A\SecondInterface'], $output['interfaces']);
        static::assertSame(['\A\FirstInterface', '\A\SecondInterface'], $output['directInterfaces']);

        static::assertThat($output['constants'], $this->arrayHasKey('FIRST_INTERFACE_CONSTANT'));
        static::assertThat($output['constants'], $this->arrayHasKey('SECOND_INTERFACE_CONSTANT'));

        static::assertThat($output['methods'], $this->arrayHasKey('methodFromFirstInterface'));
        static::assertThat($output['methods'], $this->arrayHasKey('methodFromSecondInterface'));

        // Do a couple of sanity checks.
        static::assertSame('\A\FirstInterface', $output['constants']['FIRST_INTERFACE_CONSTANT']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstInterface', $output['constants']['FIRST_INTERFACE_CONSTANT']['declaringStructure']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['methodFromFirstInterface']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstInterface', $output['methods']['methodFromFirstInterface']['declaringStructure']['fqcn']);

        static::assertSame('\A\FirstInterface', $output['constants']['FIRST_INTERFACE_CONSTANT']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstInterface', $output['constants']['FIRST_INTERFACE_CONSTANT']['declaringStructure']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['methodFromFirstInterface']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstInterface', $output['methods']['methodFromFirstInterface']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testClassTraitUsageIsCorrectlyProcessed(): void
    {
        $fileName = 'ClassTraitUsage.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame(['\A\FirstTrait', '\A\SecondTrait', '\A\BaseTrait'], $output['traits']);
        static::assertSame(['\A\FirstTrait', '\A\SecondTrait'], $output['directTraits']);

        static::assertThat($output['properties'], $this->arrayHasKey('baseTraitProperty'));
        static::assertThat($output['properties'], $this->arrayHasKey('firstTraitProperty'));
        static::assertThat($output['properties'], $this->arrayHasKey('secondTraitProperty'));

        static::assertThat($output['methods'], $this->arrayHasKey('testAmbiguous'));
        static::assertThat($output['methods'], $this->arrayHasKey('testAmbiguousAsWell'));
        static::assertThat($output['methods'], $this->arrayHasKey('baseTraitMethod'));

        // Do a couple of sanity checks.
        static::assertSame('\A\BaseClass', $output['properties']['baseTraitProperty']['declaringClass']['fqcn']);
        static::assertSame('\A\BaseTrait', $output['properties']['baseTraitProperty']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestClass', $output['properties']['firstTraitProperty']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['properties']['firstTraitProperty']['declaringStructure']['fqcn']);

        static::assertSame('\A\BaseClass', $output['methods']['baseTraitMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\BaseTrait', $output['methods']['baseTraitMethod']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestClass', $output['methods']['test1']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['methods']['test1']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestClass', $output['methods']['overriddenInBaseAndChild']['declaringClass']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['overriddenInBaseAndChild']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestClass', $output['methods']['overriddenInChild']['declaringClass']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['overriddenInChild']['declaringStructure']['fqcn']);

        // Test the 'as' keyword for renaming trait method.
        static::assertThat($output['methods'], $this->arrayHasKey('test1'));
        static::assertThat($output['methods'], $this->logicalNot($this->arrayHasKey('test')));

        static::assertTrue($output['methods']['test1']['isPrivate']);

        static::assertSame('\A\TestClass', $output['methods']['testAmbiguous']['declaringClass']['fqcn']);
        static::assertSame('\A\SecondTrait', $output['methods']['testAmbiguous']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestClass', $output['methods']['testAmbiguousAsWell']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['methods']['testAmbiguousAsWell']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testClassTraitAliasWithoutAccessModifier(): void
    {
        $fileName = 'ClassTraitAliasWithoutAccessModifier.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertFalse($output['methods']['test1']['isPublic']);
        static::assertTrue($output['methods']['test1']['isProtected']);
        static::assertFalse($output['methods']['test1']['isPrivate']);
    }

    /**
     * @return void
     */
    public function testClassTraitAliasWithAccessModifier(): void
    {
        $fileName = 'ClassTraitAliasWithAccessModifier.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertFalse($output['methods']['test1']['isPublic']);
        static::assertFalse($output['methods']['test1']['isProtected']);
        static::assertTrue($output['methods']['test1']['isPrivate']);
    }

    /**
     * @return void
     */
    public function testMethodOverrideDataIsCorrectWhenClassHasMethodThatIsAlsoDefinedByOneOfItsOwnTraits(): void
    {
        $fileName = 'ClassOverridesOwnTraitMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['override']['declaringClass']['fqcn']);
        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['override']['declaringStructure']['fqcn']);

        static::assertEmpty($output['methods']['someMethod']['implementations']);
    }

    /**
     * @return void
     */
    public function testMethodOverrideDataIsCorrectWhenClassHasMethodThatIsAlsoDefinedByOneOfItsOwnTraitsAndByTheParent(): void
    {
        $fileName = 'ClassOverridesTraitAndParentMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame('\A\BaseClass', $output['methods']['someMethod']['override']['declaringClass']['fqcn']);
        static::assertSame('\A\BaseClass', $output['methods']['someMethod']['override']['declaringStructure']['fqcn']);

        static::assertEmpty($output['methods']['someMethod']['implementations']);
    }

    /**
     * @return void
     */
    public function testTraitTraitUsageIsCorrectlyProcessed(): void
    {
        $fileName = 'TraitTraitUsage.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestTrait');

        static::assertSame(['\A\FirstTrait', '\A\SecondTrait'], $output['traits']);
        static::assertSame(['\A\FirstTrait', '\A\SecondTrait'], $output['directTraits']);

        static::assertThat($output['properties'], $this->arrayHasKey('firstTraitProperty'));
        static::assertThat($output['properties'], $this->arrayHasKey('secondTraitProperty'));

        static::assertThat($output['methods'], $this->arrayHasKey('testAmbiguous'));
        static::assertThat($output['methods'], $this->arrayHasKey('testAmbiguousAsWell'));

        // Do a couple of sanity checks.
        static::assertSame('\A\TestTrait', $output['properties']['firstTraitProperty']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['properties']['firstTraitProperty']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestTrait', $output['methods']['test1']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['methods']['test1']['declaringStructure']['fqcn']);

        // Test the 'as' keyword for renaming trait method.
        static::assertThat($output['methods'], $this->arrayHasKey('test1'));
        static::assertThat($output['methods'], $this->logicalNot($this->arrayHasKey('test')));

        static::assertTrue($output['methods']['test1']['isPrivate']);

        static::assertSame('\A\TestTrait', $output['methods']['testAmbiguous']['declaringClass']['fqcn']);
        static::assertSame('\A\SecondTrait', $output['methods']['testAmbiguous']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestTrait', $output['methods']['testAmbiguousAsWell']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['methods']['testAmbiguousAsWell']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithoutAccessModifier(): void
    {
        $fileName = 'TraitTraitAliasWithoutAccessModifier.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestTrait');

        static::assertFalse($output['methods']['test1']['isPublic']);
        static::assertTrue($output['methods']['test1']['isProtected']);
        static::assertFalse($output['methods']['test1']['isPrivate']);
    }

    /**
     * @return void
     */
    public function testTraitTraitAliasWithAccessModifier(): void
    {
        $fileName = 'TraitTraitAliasWithAccessModifier.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestTrait');

        static::assertFalse($output['methods']['test1']['isPublic']);
        static::assertFalse($output['methods']['test1']['isProtected']);
        static::assertTrue($output['methods']['test1']['isPrivate']);
    }

    /**
     * @return void
     */
    public function testMethodOverrideDataIsCorrectWhenTraitHasMethodThatIsAlsoDefinedByOneOfItsOwnTraits(): void
    {
        $fileName = 'TraitOverridesOwnTraitMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestTrait');

        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame('\A\FirstTrait', $output['methods']['someMethod']['override']['declaringClass']['fqcn']);
        static::assertSame('\A\FirstTrait', $output['methods']['someMethod']['override']['declaringStructure']['fqcn']);

        static::assertEmpty($output['methods']['someMethod']['implementations']);
    }

    /**
     * @return void
     */
    public function testMethodOverrideDataIsCorrectWhenInterfaceOverridesParentInterfaceMethod(): void
    {
        $fileName = 'InterfaceOverridesParentInterfaceMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestInterface');

        static::assertSame('\A\TestInterface', $output['methods']['interfaceMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestInterface', $output['methods']['interfaceMethod']['declaringStructure']['fqcn']);

        static::assertSame('\A\BaseInterface', $output['methods']['interfaceMethod']['override']['declaringClass']['fqcn']);
        static::assertSame('\A\BaseInterface', $output['methods']['interfaceMethod']['override']['declaringStructure']['fqcn']);

        static::assertEmpty($output['methods']['interfaceMethod']['implementations']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationDataIsCorrectWhenTraitMethodIndirectlyImplementsInterfaceMethod(): void
    {
        $fileName = 'TraitImplementsInterfaceMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame('\A\TestInterface', $output['methods']['someMethod']['implementations'][0]['declaringClass']['fqcn']);
        static::assertSame('\A\TestInterface', $output['methods']['someMethod']['implementations'][0]['declaringStructure']['fqcn']);

        static::assertNull($output['methods']['someMethod']['override']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationDataIsCorrectWhenClassReceivesSameInterfaceMethodFromTwoInterfacesAndDoesNotImplementMethod(): void
    {
        $fileName = 'ClassWithTwoInterfacesWithSameMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestInterface1', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertEmpty($output['methods']['someMethod']['implementations']);

        static::assertNull($output['methods']['someMethod']['override']);
    }

    /**
     * @return void
     */
    public function testMethodDeclaringStructureIsCorrectWhenMethodDirectlyOriginatesFromTrait(): void
    {
        $fileName = 'ClassUsingTraitMethod.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['declaringStructure']['fqcn']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationDataIsCorrectWhenClassMethodImplementsMultipleInterfaceMethodsSimultaneously(): void
    {
        $fileName = 'ClassMethodImplementsMultipleInterfaceMethods.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface1',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['someMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface1',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['someMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['someMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['someMethod']['implementations'][0]['range'],
            ],

            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface2',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['someMethod']['implementations'][1]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface2',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['someMethod']['implementations'][1]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['someMethod']['implementations'][1]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['someMethod']['implementations'][1]['range'],
            ],
        ], $output['methods']['someMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 33)
            ),
            $output['methods']['someMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 33)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(11, 33)
            ),
            $output['methods']['someMethod']['implementations'][1]['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(11, 33)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringStructure']['memberRange']
        );

        static::assertNull($output['methods']['someMethod']['override']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationDataIsCorrectWhenClassTraitMethodImplementsMultipleInterfaceMethodsSimultaneously(): void
    {
        $fileName = 'ClassTraitMethodImplementsMultipleInterfaceMethods.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestTrait', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface1',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['someMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface1',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['someMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['someMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['someMethod']['implementations'][0]['range'],
            ],

            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface2',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['someMethod']['implementations'][1]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface2',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['someMethod']['implementations'][1]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['someMethod']['implementations'][1]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['someMethod']['implementations'][1]['range'],
            ],
        ], $output['methods']['someMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 33)
            ),
            $output['methods']['someMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 33)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(11, 33)
            ),
            $output['methods']['someMethod']['implementations'][1]['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(11, 33)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringStructure']['memberRange']
        );

        static::assertNull($output['methods']['someMethod']['override']);
    }

    /**
     * @return void
     */
    public function testMethodImplementationDataIsCorrectWhenClassMethodImplementsMultipleDirectAndIndirectInterfaceMethodsSimultaneously(): void
    {
        $fileName = 'ClassMethodImplementsMultipleDirectAndIndirectInterfaceMethods.phpt';

        $output = $this->getClassInfo($fileName, 'A\TestClass');

        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringClass']['fqcn']);
        static::assertSame('\A\TestClass', $output['methods']['someMethod']['declaringStructure']['fqcn']);

        static::assertSame([
            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface1',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['someMethod']['implementations'][0]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface1',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['someMethod']['implementations'][0]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['someMethod']['implementations'][0]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['someMethod']['implementations'][0]['range'],
            ],

            [
                'declaringClass' => [
                    'fqcn'      => '\A\TestInterface2',
                    'filename'  => $this->getPathFor($fileName),
                    'range'     => $output['methods']['someMethod']['implementations'][1]['declaringClass']['range'],
                    'type'      => 'interface',
                ],

                'declaringStructure' => [
                    'fqcn'            => '\A\TestInterface2',
                    'filename'        => $this->getPathFor($fileName),
                    'range'           => $output['methods']['someMethod']['implementations'][1]['declaringStructure']['range'],
                    'type'            => 'interface',
                    'memberRange'     => $output['methods']['someMethod']['implementations'][1]['declaringStructure']['memberRange'],
                ],

                'range' => $output['methods']['someMethod']['implementations'][1]['range'],
            ],
        ], $output['methods']['someMethod']['implementations']);

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 33)
            ),
            $output['methods']['someMethod']['implementations'][0]['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(4, 0),
                new Position(7, 1)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(6, 4),
                new Position(6, 33)
            ),
            $output['methods']['someMethod']['implementations'][0]['declaringStructure']['memberRange']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(11, 33)
            ),
            $output['methods']['someMethod']['implementations'][1]['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringClass']['range']
        );

        static::assertEquals(
            new Range(
                new Position(9, 0),
                new Position(12, 1)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringStructure']['range']
        );

        static::assertEquals(
            new Range(
                new Position(11, 4),
                new Position(11, 33)
            ),
            $output['methods']['someMethod']['implementations'][1]['declaringStructure']['memberRange']
        );

        static::assertNull($output['methods']['someMethod']['override']);
    }

    /**
     * @return void
     */
    public function testSpecialTypesAreCorrectlyResolved(): void
    {
        $fileName = 'ResolveSpecialTypes.phpt';

        $output = $this->getClassInfo($fileName, 'A\childClass');

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['properties']['basePropSelf']['types']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['properties']['basePropStatic']['types']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['properties']['basePropThis']['types']);

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['properties']['propSelf']['types']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['properties']['propStatic']['types']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['properties']['propThis']['types']);

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['methods']['baseMethodSelf']['returnTypes']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['baseMethodStatic']['returnTypes']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['baseMethodThis']['returnTypes']);

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['methodSelf']['returnTypes']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['methodStatic']['returnTypes']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['methodThis']['returnTypes']);

        static::assertSame([
            [
                'type'         => '\A\childClass',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['methodOwnClassName']['returnTypes']);

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['methods']['baseMethodWithParameters']['parameters'][0]['types']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['baseMethodWithParameters']['parameters'][1]['types']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\childClass',
            ],
        ], $output['methods']['baseMethodWithParameters']['parameters'][2]['types']);

        $output = $this->getClassInfo($fileName, 'A\ParentClass');

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['properties']['basePropSelf']['types']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['properties']['basePropStatic']['types']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['properties']['basePropThis']['types']);

        static::assertSame([
            [
                'type'         => 'self',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['methods']['baseMethodSelf']['returnTypes']);

        static::assertSame([
            [
                'type'         => 'static',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['methods']['baseMethodStatic']['returnTypes']);

        static::assertSame([
            [
                'type'         => '$this',
                'resolvedType' => '\A\ParentClass',
            ],
        ], $output['methods']['baseMethodThis']['returnTypes']);
    }

    /**
     * @return void
     */
    public function testSkipsInterfaceImplementedTwice(): void
    {
        $fileName = 'InterfaceImplementedTwice.phpt';

        $output = $this->getClassInfo($fileName, '\A\TestClass');

        static::assertSame(['\A\I'], $output['interfaces']);
    }

    /**
     * @return void
     */
    public function testSkipsTraitUsedTwice(): void
    {
        $fileName = 'TraitUsedTwice.phpt';

        $output = $this->getClassInfo($fileName, '\A\TestClass');

        static::assertSame(['\A\T', '\A\T2'], $output['traits']);
    }

    /**
     * @return void
     */
    public function testSkipsInterfaceExtendedTwice(): void
    {
        $fileName = 'InterfaceExtendedTwice.phpt';

        $output = $this->getClassInfo($fileName, '\A\TestInterface');

        static::assertSame(['\A\I'], $output['parents']);
    }

    /**
     * @return void
     */
    public function testUnresolvedReturnType(): void
    {
        $fileName = 'UnresolvedReturnType.phpt';

        $output = $this->getClassInfo($fileName, '\A\TestClass');

        static::assertSame([
            [
                'type'         => '\DateTime',
                'resolvedType' => '\DateTime',
            ],
        ], $output['methods']['foo']['returnTypes']);
    }

    /**
     * @expectedException \UnexpectedValueException
     *
     * @return void
     */
    public function testFailsOnUnknownClass(): void
    {
        $output = $this->getClassInfo('SimpleClass.phpt', 'DoesNotExist');
    }

    /**
     * @return void
     */
    public function testCircularDependencyWithClassExtendingItselfDoesNotLoop(): void
    {
        $fileName = 'CircularDependencyExtends.phpt';

        static::assertNotNull($this->getClassInfo($fileName, 'A\C'));
    }

    /**
     * @return void
     */
    public function testCircularDependencyWithClassImplementingItselfDoesNotLoop(): void
    {
        $fileName = 'CircularDependencyImplements.phpt';

        static::assertNotNull($this->getClassInfo($fileName, 'A\C'));
    }

    /**
     * @return void
     */
    public function testCircularDependencyWithClassUsingItselfAsTraitDoesNotLoop(): void
    {
        $fileName = 'CircularDependencyUses.phpt';

        static::assertNotNull($this->getClassInfo($fileName, 'A\C'));
    }

    /**
     * @return void
     */
    public function testInterfaceIncorrectlyUsingTraitDoesNotCrash(): void
    {
        $fileName = 'InterfaceIncorrectlyUsesTrait.phpt';

        static::assertNotNull($this->getClassInfo($fileName, 'A\I'));
    }

    /**
     * @param string $file
     * @param string $fqcn
     *
     * @return array
     */
    private function getClassInfo(string $file, string $fqcn): array
    {
        $path = $this->getPathFor($file);

        $this->indexTestFile($this->container, $path);

        $command = $this->container->get('classInfoCommand');

        return $command->getClassInfo($fqcn);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getPathFor(string $file): string
    {
        return __DIR__ . '/ClassInfoCommandTest/' . $file;
    }
}
