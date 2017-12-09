<?php

namespace PhpIntegrator\Tests\Integration\Analysis\Typing\Deduction;

use ReflectionClass;

use PhpIntegrator\Indexing\FileNotFoundStorageException;

use PhpIntegrator\UserInterface\Command\DeduceTypesCommand;

use PhpIntegrator\Tests\Integration\AbstractIntegrationTest;

class ExpressionTypeDeducerTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTypeOverrideAnnotations(): void
    {
        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$a');

        static::assertSame(['\Traversable'], $output);

        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$b');

        static::assertSame(['\Traversable'], $output);

        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$c');

        static::assertSame(['\A\C', 'null'], $output);

        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$d');

        static::assertSame(['\A\D'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyResolvesThisInClass(): void
    {
        $output = $this->deduceTypesFromExpression('ThisInClass.phpt', '$this');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyResolvesThisOutsideClass(): void
    {
        $output = $this->deduceTypesFromExpression('ThisOutsideClass.phpt', '$this');

        static::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesFunctionTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHint.phpt', '$b');

        static::assertSame(['\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNullableFunctionTypeHintsViaDefaultValue(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintDefaultValue.phpt', '$b');

        static::assertSame(['\A\B', 'null'], $output);
    }
    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNullableFunctionTypeHintsViaNullableSyntax(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintNullableSyntax.phpt', '$b');

        static::assertSame(['\A\B', 'null'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesFunctionDocblocks(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterDocblock.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesMethodTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('MethodParameterTypeHint.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesMethodDocblocks(): void
    {
        $output = $this->deduceTypesFromExpression('MethodParameterDocblock.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesClosureTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('ClosureParameterTypeHint.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyMovesBeyondClosureScopeForVariableUses(): void
    {
        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$b');

        static::assertSame(['\A\B'], $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$c');

        static::assertSame(['\A\C'], $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$d');

        static::assertSame(['\A\D'], $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$e');

        static::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesCatchBlockTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('CatchBlockTypeHint.phpt', '$e');

        static::assertSame(['\UnexpectedValueException'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIf.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithInstanceofAndProperty(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithProperty.phpt', '$this->foo');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithInstanceofAndPropertyWithParentKeyword(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithPropertyWithParentKeyword.phpt', 'parent::$foo');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithInstanceofAndStaticPropertyWithClassName(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithStaticPropertyWithClassName.phpt', 'Test::$foo');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithInstanceofAndStaticPropertyWithSelfKeyword(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithStaticPropertyWithSelfKeyword.phpt', 'self::$foo');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithInstanceofAndStaticPropertyWithStaticKeyword(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithStaticPropertyWithStaticKeyword.phpt', 'static::$foo');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithInstanceofAndVariableInsideCondition(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofComplexIfVariableInsideCondition.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithInstanceofAndAnd(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofComplexIfAnd.phpt', '$b');

        static::assertSame(['\A\B', '\A\C', '\A\D'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithInstanceofAndOr(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofComplexIfOr.phpt', '$b');

        static::assertSame(['\A\B', '\A\C', '\A\D', '\A\E'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyIfStatementWithInstanceofAndOrTakesPrecedenceOverFunctionTypeHint(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfOrWithTypeHint.phpt', '$b');

        static::assertSame(['\A\B', '\A\C'], $output);
    }

    /**
     * @return void
     */
    public function testIfWithInstanceofContainingIfWithDifferentInstanceofGivesNestedTypePrecedenceOverFirst(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofNestedIf.phpt', '$b');

        static::assertSame(['\A\A'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNestedIfStatementWithInstanceofAndNegation(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofNestedIfWithNegation.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNestedIfStatementWithInstanceofAndReassignment(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofNestedIfReassignment.phpt', '$b');

        static::assertSame(['\A\A'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithNotInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('IfNotInstanceof.phpt', '$b');

        static::assertSame(['\A\A'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithNotStrictlyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfNotStrictlyEqualsNull.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithNotLooselyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfNotLooselyEqualsNull.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithStrictlyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfStrictlyEqualsNull.phpt', '$b');

        static::assertSame(['null'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithLooselyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfLooselyEqualsNull.phpt', '$b');

        static::assertSame(['null'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithTruthy(): void
    {
        $output = $this->deduceTypesFromExpression('IfTruthy.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesIfStatementWithFalsy(): void
    {
        $output = $this->deduceTypesFromExpression('IfFalsy.phpt', '$b');

        static::assertSame(['null'], $output);
    }

    /**
     * @return void
     */
    public function testTypeOverrideAnnotationsStillTakePrecedenceOverConditionals(): void
    {
        $output = $this->deduceTypesFromExpression('IfWithTypeOverride.phpt', '$b');

        static::assertSame(['string'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesComplexIfStatementWithVariableHandlingFunction(): void
    {
        $output = $this->deduceTypesFromExpression('IfVariableHandlingFunction.phpt', '$b');

        static::assertSame([
            'array',
            'bool',
            'callable',
            'float',
            'int',
            'null',
            'string',
            'object',
            'resource'
        ], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyTreatsIfConditionAsSeparateScope(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfSeparateScope.phpt', '$b');

        static::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesElseIfStatementWithInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofElseIf.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testIfStatementCorrectlyNarrowsDownDetectedTypeOfStringVariable(): void
    {
        $output = $this->deduceTypesFromExpression('IfStatementNarrowsTypeOfStringVariable.phpt', '$b');

        static::assertSame(['string'], $output);
    }

    /**
     * @return void
     */
    public function testNestedIfStatementDoesNotExpandTypeListAgainIfPreviousIfStatementWasSpecific(): void
    {
        $output = $this->deduceTypesFromExpression('IfStatementDoesNotExpandTypeListOfVariable.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyConfinesTreatsElseIfConditionAsSeparateScope(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofElseIfSeparateScope.phpt', '$b');

        static::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTernaryExpressionWithInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofTernary.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyStartsFromTheDocblockTypeOfPropertiesBeforeApplyingConditionals(): void
    {
        $output = $this->deduceTypesFromExpression('IfWithProperty.phpt', '$b->foo');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyConfinesTreatsTernaryExpressionConditionAsSeparateScope(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofTernarySeparateScope.phpt', '$b');

        static::assertSame([], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTernaryExpression(): void
    {
        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$a');

        static::assertSame(['\A'], $output);

        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$b');

        static::assertSame(['\B'], $output);

        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$c');

        static::assertSame(['\C', 'null'], $output);

        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$d');

        static::assertSame(['\A', '\C', 'null'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesForeach(): void
    {
        $output = $this->deduceTypesFromExpression('Foreach.phpt', '$a');

        static::assertSame(['\DateTime'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesForeachWithStaticMethodCallReturningArrayWithSelfObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachWithStaticMethodCallReturningArrayWithSelfObjects.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesForeachWithStaticMethodCallReturningArrayWithStaticObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachWithStaticMethodCallReturningArrayWithStaticObjects.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesAssignments(): void
    {
        $output = $this->deduceTypesFromExpression('Assignment.phpt', '$a');

        static::assertSame(['\DateTime'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyIgnoresAssignmentsOutOfScope(): void
    {
        $output = $this->deduceTypesFromExpression('AssignmentOutOfScope.phpt', '$a');

        static::assertSame(['\DateTime'], $output);
    }

    /**
     * @return void
     */
    public function testDocblockTakesPrecedenceOverTypeHint(): void
    {
        $output = $this->deduceTypesFromExpression('DocblockPrecedence.phpt', '$b');

        static::assertSame(['\B'], $output);
    }

    /**
     * @return void
     */
    public function testVariadicTypesForParametersAreCorrectlyAnalyzed(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionVariadicParameter.phpt', '$b');

        static::assertSame(['\A\B[]'], $output);
    }

    /**
     * @return void
     */
    public function testSpecialTypesForParametersResolveCorrectly(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintSpecial.phpt', '$a');

        static::assertSame(['\A\C'], $output);

        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintSpecial.phpt', '$b');

        static::assertSame(['\A\C'], $output);

        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintSpecial.phpt', '$c');

        static::assertSame(['\A\C'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesStaticPropertyAccess(): void
    {
        $result = $this->deduceTypesFromExpression(
            'StaticPropertyAccess.phpt',
            'Bar::$testProperty'
        );

        static::assertSame(['\DateTime'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesSelf(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Self.phpt',
            'self::$testProperty'
        );

        static::assertSame(['\B'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesStatic(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Static.phpt',
            'static::$testProperty'
        );

        static::assertSame(['\B'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesParent(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Parent.phpt',
            'parent::$testProperty'
        );

        static::assertSame(['\B'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesThis(): void
    {
        $result = $this->deduceTypesFromExpression(
            'This.phpt',
            '$this->testProperty'
        );

        static::assertSame(['\B'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesVariables(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Variable.phpt',
            '$var->testProperty'
        );

        static::assertSame(['\B'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesGlobalFunctions(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunction.phpt',
            '\global_function()'
        );

        static::assertSame(['\B', 'null'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesUnqualifiedGlobalFunctions(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunction.phpt',
            'global_function()'
        );

        static::assertSame(['\B', 'null'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesGlobalFunctionsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunctionInNamespace.phpt',
            '\N\global_function()'
        );

        static::assertSame(['\N\B', 'null'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesUnqualifiedGlobalFunctionsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunctionInNamespace.phpt',
            'global_function()'
        );

        static::assertSame(['\N\B', 'null'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesGlobalConstants(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstant.phpt',
            '\GLOBAL_CONSTANT'
        );

        static::assertSame(['string'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesUnqualifiedGlobalConstants(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstant.phpt',
            'GLOBAL_CONSTANT'
        );

        static::assertSame(['string'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesGlobalConstantsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstantInNamespace.phpt',
            '\N\GLOBAL_CONSTANT'
        );

        static::assertSame(['string'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesUnqualifiedGlobalConstantsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstantInNamespace.phpt',
            'GLOBAL_CONSTANT'
        );

        static::assertSame(['string'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesGlobalConstantsAssignedToOtherGlobalConstants(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstant.phpt',
            '\ANOTHER_GLOBAL_CONSTANT'
        );

        static::assertSame(['string'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesClosures(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Closure.phpt',
            '$var'
        );

        static::assertSame(['\Closure'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTypeOfElementsOfArrayWithObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfArrayWithObjects.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTypeOfElementsOfString(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfString.phpt', '$b');

        static::assertSame(['string'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTypeOfElementsOfTypeNotAccessibleAsArray(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfTypeNotAccessibleAsArray.phpt', '$b');

        static::assertSame(['mixed'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTypeOfElementsOfArrayWithObjectsOfMultipleTypes(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfArrayWithObjectsOfMultipleTypes.phpt', '$b');

        static::assertSame(['\A\B', '\A\C'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesTypeOfElementsOfArrayWithSelfElementsReturnedByStaticMethodCall(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfArrayWithSelfElementsFromStaticMethodCall.phpt', '$b');

        static::assertSame(['\A\B'], $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNewWithStatic(): void
    {
        $result = $this->deduceTypesFromExpression(
            'NewWithKeyword.phpt',
            'new static'
        );

        static::assertSame(['\Bar'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNewWithSelf(): void
    {
        $result = $this->deduceTypesFromExpression(
            'NewWithKeyword.phpt',
            'new self'
        );

        static::assertSame(['\Bar'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesNewWithParent(): void
    {
        $result = $this->deduceTypesFromExpression(
            'NewWithKeyword.phpt',
            'new parent'
        );

        static::assertSame(['\Foo'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesClone(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Clone.phpt',
            'clone $var'
        );

        static::assertSame(['\Bar'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesLongerChains(): void
    {
        $result = $this->deduceTypesFromExpression(
            'LongerChain.phpt',
            '$this->testProperty->aMethod()->anotherProperty'
        );

        static::assertSame(['\DateTime'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyAnalyzesScalarTypes(): void
    {
        $file = 'ScalarType.phpt';

        static::assertSame(['int'], $this->deduceTypesFromExpression($file, '5'));
        static::assertSame(['int'], $this->deduceTypesFromExpression($file, '05'));
        static::assertSame(['int'], $this->deduceTypesFromExpression($file, '0x5'));
        static::assertSame(['float'], $this->deduceTypesFromExpression($file, '5.5'));
        static::assertSame(['bool'], $this->deduceTypesFromExpression($file, 'true'));
        static::assertSame(['bool'], $this->deduceTypesFromExpression($file, 'false'));
        static::assertSame(['string'], $this->deduceTypesFromExpression($file, '"test"'));
        static::assertSame(['string'], $this->deduceTypesFromExpression($file, '\'test\''));
        static::assertSame(['array'], $this->deduceTypesFromExpression($file, '[$test1, function() {}]'));
        static::assertSame(['array'], $this->deduceTypesFromExpression($file, 'array($test1, function() {})'));

        static::assertSame(['string'], $this->deduceTypesFromExpression($file, '"
            test
        "'));

        static::assertSame(['string'], $this->deduceTypesFromExpression($file, '\'
            test
        \''));
    }

    /**
     * @return void
     */
    public function testCorrectlyProcessesSelfAssign(): void
    {
        $result = $this->deduceTypesFromExpression(
            'SelfAssign.phpt',
            '$foo1'
        );

        static::assertSame(['\A\Foo'], $result);

        $result = $this->deduceTypesFromExpression(
            'SelfAssign.phpt',
            '$foo2'
        );

        static::assertSame(['\A\Foo'], $result);

        $result = $this->deduceTypesFromExpression(
            'SelfAssign.phpt',
            '$foo3'
        );

        static::assertSame(['\A\Foo'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyProcessesStaticMethodCallAssignedToVariableWithFqcnWithLeadingSlash(): void
    {
        $result = $this->deduceTypesFromExpression(
            'StaticMethodCallFqcnLeadingSlash.phpt',
            '$data'
        );

        static::assertSame(['\A\B'], $result);
    }

    /**
     * @return void
     */
    public function testCorrectlyReturnsMultipleTypes(): void
    {
        $result = $this->deduceTypesFromExpression(
            'MultipleTypes.phpt',
            '$this->testProperty'
        );

        static::assertSame([
            'string',
            'int',
            '\Foo',
            '\Bar'
        ], $result);
    }

    /**
     * @return void
     */
    public function testAnonymousClass(): void
    {
        $fileName = 'AnonymousClass.phpt';

        $result = $this->deduceTypesFromExpression($fileName, '$test');

        $filePath = $this->getFilePath($fileName);

        static::assertSame([
            '\\(anonymous_' . md5($filePath) . '_19)'
        ], $result);
    }

    /**
     * @return void
     */
    public function testVariableInCatchBlockWithMultipleExceptionTypeHintsHasMultipleTypes(): void
    {
        $result = $this->deduceTypesFromExpression(
            'CatchMultipleExceptionTypes.phpt',
            '$e'
        );

        static::assertSame([
            '\Exception',
            '\Throwable'
        ], $result);
    }

    /**
     * @return void
     */
    public function testIgnoreLastElement(): void
    {
        $result = $this->deduceTypesFromExpression(
            'AssignmentIgnoreLastElement.phpt',
            '$a->test',
            true
        );

        static::assertSame(['\DateTime'], $result);
    }

    /**
     * @return void
     */
    public function testMetaStaticMethodTypesWithMatchingFqcn(): void
    {
        $result = $this->deduceTypesFromExpressionWithMeta(
            'MetaStaticMethodTypesMatchingFqcn.phpt',
            'MetaStaticMethodTypesMetaFile.phpt',
            '$var'
        );

        static::assertSame(['\B\Bar'], $result);
    }

    /**
     * @return void
     */
    public function testMetaStaticMethodTypesDoesNotTryToResolveDynamicMethodCall(): void
    {
        $result = $this->deduceTypesFromExpression(
            'MetaStaticMethodTypesDoesNotTryToResolveDynamicMethodCall.phpt',
            '$var'
        );

        static::assertSame([], $result);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('deduceTypesCommand');

        $this->expectException(FileNotFoundStorageException::class);

        $command->deduceTypes('DoesNotExist.phpt', 'Code', 'CodeWithExpression', 1, false);
    }

    /**
     * @param string $file
     * @param string $expression
     * @param bool   $ignoreLastElement
     *
     * @return string[]
     */
    private function deduceTypesFromExpression(string $file, string $expression, bool $ignoreLastElement = false): array
    {
        $path = $this->getFilePath($file);

        $markerOffset = $this->getMarkerOffset($path, '<MARKER>');

        $this->indexTestFile($this->container, $path);

        $expressionTypeDeducer = $this->container->get('expressionTypeDeducer');

        $file = $this->container->get('storage')->getFileByPath($path);

        return $expressionTypeDeducer->deduce(
            $file,
            file_get_contents($path),
            $markerOffset,
            $expression,
            $ignoreLastElement
        );
    }

    /**
     * @param string $file
     * @param string $metaFile
     * @param string $expression
     *
     * @return array
     */
    private function deduceTypesFromExpressionWithMeta(string $file, string $metaFile, string $expression): array
    {
        $path = $this->getFilePath($file);
        $metaFilePath = __DIR__ . '/ExpressionTypeDeducerTest/' . $metaFile;

        $markerOffset = $this->getMarkerOffset($path, '<MARKER>');

        $this->indexTestFile($this->container, $metaFilePath);
        $this->indexTestFile($this->container, $path);

        $expressionTypeDeducer = $this->container->get('expressionTypeDeducer');

        $file = $this->container->get('storage')->getFileByPath($path);

        return $expressionTypeDeducer->deduce(
            $file,
            file_get_contents($path),
            $markerOffset,
            $expression,
            false
        );
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getFilePath(string $file): string
    {
        return __DIR__ . '/ExpressionTypeDeducerTest/' . $file;
    }

    /**
     * @param string $path
     * @param string $marker
     *
     * @return int
     */
    private function getMarkerOffset(string $path, string $marker): int
    {
        $testFileContents = @file_get_contents($path);

        $markerOffset = mb_strpos($testFileContents, $marker);

        return $markerOffset;
    }
}
