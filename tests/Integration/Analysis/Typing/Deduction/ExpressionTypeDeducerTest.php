<?php

namespace Serenata\Tests\Integration\Analysis\Typing\Deduction;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

use Serenata\Common\Position;

use Serenata\Indexing\FileNotFoundStorageException;

use Serenata\Tests\Integration\AbstractIntegrationTest;

use Serenata\Utility\PositionEncoding;
use Serenata\Utility\TextDocumentItem;

final class ExpressionTypeDeducerTest extends AbstractIntegrationTest
{
    /**
     * @return void
     */
    public function testTypeOverrideAnnotations(): void
    {
        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$a');

        self::assertSame('\Traversable', (string) $output);

        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$b');

        self::assertSame('\Traversable', (string) $output);

        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$c');

        self::assertSame('(\A\C | null)', (string) $output);

        $output = $this->deduceTypesFromExpression('TypeOverrideAnnotations.phpt', '$d');

        self::assertSame('\A\D', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyHandlesIntersectionTypes(): void
    {
        $output = $this->deduceTypesFromExpression('IntersectionTypes.phpt', '$a');

        // TODO: Not quite correct, but good enough for type analysis for now as they are handled the same.
        self::assertSame('(\A | \B)', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyResolvesThisInClass(): void
    {
        $output = $this->deduceTypesFromExpression('ThisInClass.phpt', '$this');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyResolvesThisOutsideClass(): void
    {
        $output = $this->deduceTypesFromExpression('ThisOutsideClass.phpt', '$this');

        self::assertSame('', (string) $output);
    }

    /**
     * @return void
     */
    public function testFunctionTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHint.phpt', '$b');

        self::assertSame('\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testNullableFunctionTypeHintsViaDefaultValue(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintDefaultValue.phpt', '$b');

        self::assertSame('(\A\B | null)', (string) $output);
    }
    /**
     * @return void
     */
    public function testNullableFunctionTypeHintsViaNullableSyntax(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintNullableSyntax.phpt', '$b');

        self::assertSame('(\A\B | null)', (string) $output);
    }

    /**
     * @return void
     */
    public function testFunctionDocblocks(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterDocblock.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testMethodTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('MethodParameterTypeHint.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testMethodDocblocks(): void
    {
        $output = $this->deduceTypesFromExpression('MethodParameterDocblock.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testClosureTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('ClosureParameterTypeHint.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyMovesBeyondClosureScopeForVariableUses(): void
    {
        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$b');

        self::assertSame('\A\B', (string) $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$c');

        self::assertSame('\A\C', (string) $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$d');

        self::assertSame('\A\D', (string) $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableUseStatement.phpt', '$e');

        self::assertSame('', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyMovesBeyondClosureScopeForArrowFunctions(): void
    {
        $output = $this->deduceTypesFromExpression('ClosureVariableArrowFunction.phpt', '$e');

        self::assertSame('\A\E', (string) $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableArrowFunction.phpt', '$d');

        self::assertSame('\A\D', (string) $output);

        $output = $this->deduceTypesFromExpression('ClosureVariableArrowFunction.phpt', '$a');

        self::assertSame('\A\A', (string) $output);
    }

    /**
     * @return void
     */
    public function testCatchBlockTypeHints(): void
    {
        $output = $this->deduceTypesFromExpression('CatchBlockTypeHint.phpt', '$e');

        self::assertSame('\UnexpectedValueException', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIf.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithInstanceofAndProperty(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithProperty.phpt', '$this->foo');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithInstanceofAndPropertyWithParentKeyword(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithPropertyWithParentKeyword.phpt', 'parent::$foo');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithInstanceofAndStaticPropertyWithClassName(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithStaticPropertyWithClassName.phpt', 'Test::$foo');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithInstanceofAndStaticPropertyWithSelfKeyword(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithStaticPropertyWithSelfKeyword.phpt', 'self::$foo');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithInstanceofAndStaticPropertyWithStaticKeyword(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfWithStaticPropertyWithStaticKeyword.phpt', 'static::$foo');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithInstanceofAndVariableInsideCondition(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofComplexIfVariableInsideCondition.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithInstanceofAndAnd(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofComplexIfAnd.phpt', '$b');

        self::assertSame('(\A\B | \A\C | \A\D)', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithInstanceofAndOr(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofComplexIfOr.phpt', '$b');

        self::assertSame('(\A\B | \A\C | \A\D | \A\E)', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyIfStatementWithInstanceofAndOrTakesPrecedenceOverFunctionTypeHint(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfOrWithTypeHint.phpt', '$b');

        self::assertSame('(\A\B | \A\C)', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfWithInstanceofContainingIfWithDifferentInstanceofGivesNestedTypePrecedenceOverFirst(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofNestedIf.phpt', '$b');

        self::assertSame('\A\A', (string) $output);
    }

    /**
     * @return void
     */
    public function testNestedIfStatementWithInstanceofAndNegation(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofNestedIfWithNegation.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testNestedIfStatementWithInstanceofAndReassignment(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofNestedIfReassignment.phpt', '$b');

        self::assertSame('\A\A', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithNotInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('IfNotInstanceof.phpt', '$b');

        self::assertSame('\A\A', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithNotStrictlyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfNotStrictlyEqualsNull.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithNotLooselyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfNotLooselyEqualsNull.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithStrictlyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfStrictlyEqualsNull.phpt', '$b');

        self::assertSame('null', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithLooselyEqualsNull(): void
    {
        $output = $this->deduceTypesFromExpression('IfLooselyEqualsNull.phpt', '$b');

        self::assertSame('null', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithTruthy(): void
    {
        $output = $this->deduceTypesFromExpression('IfTruthy.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementWithFalsy(): void
    {
        $output = $this->deduceTypesFromExpression('IfFalsy.phpt', '$b');

        self::assertSame('null', (string) $output);
    }

    /**
     * @return void
     */
    public function testTypeOverrideAnnotationsStillTakePrecedenceOverConditionals(): void
    {
        $output = $this->deduceTypesFromExpression('IfWithTypeOverride.phpt', '$b');

        self::assertSame('string', (string) $output);
    }

    /**
     * @return void
     */
    public function testComplexIfStatementWithVariableHandlingFunction(): void
    {
        $output = $this->deduceTypesFromExpression('IfVariableHandlingFunction.phpt', '$b');

        self::assertSame(
            '(array | bool | callable | float | int | null | string | object | resource)',
            (string) $output
        );
    }

    /**
     * @return void
     */
    public function testCorrectlyTreatsIfConditionAsSeparateScope(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofIfSeparateScope.phpt', '$b');

        self::assertSame('', (string) $output);
    }

    /**
     * @return void
     */
    public function testElseIfStatementWithInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofElseIf.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testIfStatementCorrectlyNarrowsDownDetectedTypeOfStringVariable(): void
    {
        $output = $this->deduceTypesFromExpression('IfStatementNarrowsTypeOfStringVariable.phpt', '$b');

        self::assertSame('string', (string) $output);
    }

    /**
     * @return void
     */
    public function testNestedIfStatementDoesNotExpandTypeListAgainIfPreviousIfStatementWasSpecific(): void
    {
        $output = $this->deduceTypesFromExpression('IfStatementDoesNotExpandTypeListOfVariable.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyConfinesTreatsElseIfConditionAsSeparateScope(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofElseIfSeparateScope.phpt', '$b');

        self::assertSame('', (string) $output);
    }

    /**
     * @return void
     */
    public function testTernaryExpressionWithInstanceof(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofTernary.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyStartsFromTheDocblockTypeOfPropertiesBeforeApplyingConditionals(): void
    {
        $output = $this->deduceTypesFromExpression('IfWithProperty.phpt', '$b->foo');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyConfinesTreatsTernaryExpressionConditionAsSeparateScope(): void
    {
        $output = $this->deduceTypesFromExpression('InstanceofTernarySeparateScope.phpt', '$b');

        self::assertSame('', (string) $output);
    }

    /**
     * @return void
     */
    public function testTernaryExpression(): void
    {
        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$a');

        self::assertSame('\A', (string) $output);

        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$b');

        self::assertSame('\B', (string) $output);

        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$c');

        self::assertSame('(\C | null)', (string) $output);

        $output = $this->deduceTypesFromExpression('TernaryExpression.phpt', '$d');

        self::assertSame('(\A | \C | null)', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeach(): void
    {
        $output = $this->deduceTypesFromExpression('Foreach.phpt', '$a');

        self::assertSame('\DateTime', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeachValueWithGenericSequentialArraySyntax(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachValueWithGenericSequentialArraySyntax.phpt', '$a');

        self::assertSame('\DateTime', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeachValueWithGenericAssociativeArraySyntax(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachValueWithGenericAssociativeArraySyntax.phpt', '$a');

        self::assertSame('\DateTime', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeachValueWithStaticMethodCallReturningArrayWithSelfObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachValueWithStaticMethodCallReturningArrayWithSelfObjects.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeachValueWithStaticMethodCallReturningArrayWithStaticObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachValueWithStaticMethodCallReturningArrayWithStaticObjects.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeachValueWithGenericIterableWithSyntaxOneArgument(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachValueWithGenericIterableSyntaxWithOneArgument.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testForeachValueWithGenericIterableWithSyntaxTwoArguments(): void
    {
        $output = $this->deduceTypesFromExpression('ForeachValueWithGenericIterableSyntaxWithTwoArguments.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testAssignments(): void
    {
        $output = $this->deduceTypesFromExpression('Assignment.phpt', '$a');

        self::assertSame('\DateTime', (string) $output);
    }

    /**
     * @return void
     */
    public function testCorrectlyIgnoresAssignmentsOutOfScope(): void
    {
        $output = $this->deduceTypesFromExpression('AssignmentOutOfScope.phpt', '$a');

        self::assertSame('\DateTime', (string) $output);
    }

    /**
     * @return void
     */
    public function testDocblockTakesPrecedenceOverTypeHint(): void
    {
        $output = $this->deduceTypesFromExpression('DocblockPrecedence.phpt', '$b');

        self::assertSame('\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testVariadicTypesForParametersAreCorrectlyAnalyzed(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionVariadicParameter.phpt', '$b');

        self::assertSame('\A\B[]', (string) $output);
    }

    /**
     * @return void
     */
    public function testSpecialTypesForParametersResolveCorrectly(): void
    {
        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintSpecial.phpt', '$a');

        self::assertSame('\A\C', (string) $output);

        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintSpecial.phpt', '$b');

        self::assertSame('\A\C', (string) $output);

        $output = $this->deduceTypesFromExpression('FunctionParameterTypeHintSpecial.phpt', '$c');

        self::assertSame('\A\C', (string) $output);
    }

    /**
     * @return void
     */
    public function testStaticPropertyAccess(): void
    {
        $result = $this->deduceTypesFromExpression(
            'StaticPropertyAccess.phpt',
            'Bar::$testProperty'
        );

        self::assertSame('\DateTime', (string) $result);
    }

    /**
     * @return void
     */
    public function testSelf(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Self.phpt',
            'self::$testProperty'
        );

        self::assertSame('\B', (string) $result);
    }

    /**
     * @return void
     */
    public function testStatic(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Static.phpt',
            'self::$testProperty'
        );

        self::assertSame('\B', (string) $result);
    }

    /**
     * @return void
     */
    public function testParent(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Parent.phpt',
            'parent::$testProperty'
        );

        self::assertSame('\B', (string) $result);
    }

    /**
     * @return void
     */
    public function testThis(): void
    {
        $result = $this->deduceTypesFromExpression(
            'This.phpt',
            '$this->testProperty'
        );

        self::assertSame('\B', (string) $result);
    }

    /**
     * @return void
     */
    public function testVariables(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Variable.phpt',
            '$var->testProperty'
        );

        self::assertSame('\B', (string) $result);
    }

    /**
     * @return void
     */
    public function testGlobalFunctions(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunction.phpt',
            '\global_function()'
        );

        self::assertSame('(\B | null)', (string) $result);
    }

    /**
     * @return void
     */
    public function testUnqualifiedGlobalFunctions(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunction.phpt',
            'global_function()'
        );

        self::assertSame('(\B | null)', (string) $result);
    }

    /**
     * @return void
     */
    public function testGlobalFunctionsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunctionInNamespace.phpt',
            '\N\global_function()'
        );

        self::assertSame('(\N\B | null)', (string) $result);
    }

    /**
     * @return void
     */
    public function testUnqualifiedGlobalFunctionsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalFunctionInNamespace.phpt',
            'global_function()'
        );

        self::assertSame('(\N\B | null)', (string) $result);
    }

    /**
     * @return void
     */
    public function testGlobalConstants(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstant.phpt',
            '\GLOBAL_CONSTANT'
        );

        self::assertSame('string', (string) $result);
    }

    /**
     * @return void
     */
    public function testUnqualifiedGlobalConstants(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstant.phpt',
            'GLOBAL_CONSTANT'
        );

        self::assertSame('string', (string) $result);
    }

    /**
     * @return void
     */
    public function testGlobalConstantsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstantInNamespace.phpt',
            '\N\GLOBAL_CONSTANT'
        );

        self::assertSame('string', (string) $result);
    }

    /**
     * @return void
     */
    public function testUnqualifiedGlobalConstantsInNamespace(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstantInNamespace.phpt',
            'GLOBAL_CONSTANT'
        );

        self::assertSame('string', (string) $result);
    }

    /**
     * @return void
     */
    public function testGlobalConstantsAssignedToOtherGlobalConstants(): void
    {
        $result = $this->deduceTypesFromExpression(
            'GlobalConstant.phpt',
            '\ANOTHER_GLOBAL_CONSTANT'
        );

        self::assertSame('string', (string) $result);
    }

    /**
     * @return void
     */
    public function testClosures(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Closure.phpt',
            '$var'
        );

        self::assertSame('\Closure', (string) $result);
    }

    /**
     * @return void
     */
    public function testArrowFunctionClosures(): void
    {
        $result = $this->deduceTypesFromExpression(
            'ArrowFunctionClosure.phpt',
            '$var'
        );

        self::assertSame('\Closure', (string) $result);
    }

    /**
     * @return void
     */
    public function testTypeOfElementsOfArrayWithObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfArrayWithObjects.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testArrayElementOfGenericAssociativeArrayWithObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfGenericAssociativeArrayWithObjects.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testArrayElementOfGenericSequentialArrayWithObjects(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfGenericSequentialArrayWithObjects.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testTypeOfElementsOfString(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfString.phpt', '$b');

        self::assertSame('string', (string) $output);
    }

    /**
     * @return void
     */
    public function testTypeOfElementsOfTypeNotAccessibleAsArray(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfTypeNotAccessibleAsArray.phpt', '$b');

        self::assertSame('mixed', (string) $output);
    }

    /**
     * @return void
     */
    public function testTypeOfElementsOfArrayWithObjectsOfMultipleTypes(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfArrayWithObjectsOfMultipleTypes.phpt', '$b');

        self::assertSame('(\A\B | \A\C)', (string) $output);
    }

    /**
     * @return void
     */
    public function testTypeOfElementsOfArrayWithSelfElementsReturnedByStaticMethodCall(): void
    {
        $output = $this->deduceTypesFromExpression('ArrayElementOfArrayWithSelfElementsFromStaticMethodCall.phpt', '$b');

        self::assertSame('\A\B', (string) $output);
    }

    /**
     * @return void
     */
    public function testNewWithStatic(): void
    {
        $result = $this->deduceTypesFromExpression(
            'NewWithKeyword.phpt',
            'new static'
        );

        self::assertSame('\Bar', (string) $result);
    }

    /**
     * @return void
     */
    public function testNewWithSelf(): void
    {
        $result = $this->deduceTypesFromExpression(
            'NewWithKeyword.phpt',
            'new self'
        );

        self::assertSame('\Bar', (string) $result);
    }

    /**
     * @return void
     */
    public function testNewWithParent(): void
    {
        $result = $this->deduceTypesFromExpression(
            'NewWithKeyword.phpt',
            'new parent'
        );

        self::assertSame('\Foo', (string) $result);
    }

    /**
     * @return void
     */
    public function testClone(): void
    {
        $result = $this->deduceTypesFromExpression(
            'Clone.phpt',
            'clone $var'
        );

        self::assertSame('\Bar', (string) $result);
    }

    /**
     * @return void
     */
    public function testLongerChains(): void
    {
        $result = $this->deduceTypesFromExpression(
            'LongerChain.phpt',
            '$this->testProperty->aMethod()->anotherProperty'
        );

        self::assertSame('\DateTime', (string) $result);
    }

    /**
     * @return void
     */
    public function testScalarTypes(): void
    {
        $file = 'ScalarType.phpt';

        self::assertSame('int', (string) $this->deduceTypesFromExpression($file, '5'));
        self::assertSame('int', (string) $this->deduceTypesFromExpression($file, '05'));
        self::assertSame('int', (string) $this->deduceTypesFromExpression($file, '0x5'));
        self::assertSame('float', (string) $this->deduceTypesFromExpression($file, '5.5'));
        self::assertSame('bool', (string) $this->deduceTypesFromExpression($file, 'true'));
        self::assertSame('bool', (string) $this->deduceTypesFromExpression($file, 'false'));
        self::assertSame('string', (string) $this->deduceTypesFromExpression($file, '"test"'));
        self::assertSame('string', (string) $this->deduceTypesFromExpression($file, '\'test\''));
        self::assertSame('array', (string) $this->deduceTypesFromExpression($file, '[$test1, function() {}]'));
        self::assertSame('array', (string) $this->deduceTypesFromExpression($file, 'array($test1, function() {})'));

        self::assertSame('string', (string) $this->deduceTypesFromExpression($file, '"
            test
        "'));

        self::assertSame('string', (string) $this->deduceTypesFromExpression($file, '\'
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

        self::assertSame('\A\Foo', (string) $result);

        $result = $this->deduceTypesFromExpression(
            'SelfAssign.phpt',
            '$foo2'
        );

        self::assertSame('\A\Foo', (string) $result);

        $result = $this->deduceTypesFromExpression(
            'SelfAssign.phpt',
            '$foo3'
        );

        self::assertSame('\A\Foo', (string) $result);
    }

    /**
     * @return void
     */
    public function testQualifiedFunctionCallRelativeToImport(): void
    {
        $output = $this->deduceTypesFromExpression('QualifiedFunctionCallRelativeToImport.phpt', '$test');

        self::assertSame('string', (string) $output);
    }

    /**
     * @return void
     */
    public function testQualifiedConstantFetchRelativeToImport(): void
    {
        $output = $this->deduceTypesFromExpression('QualifiedConstantFetchRelativeToImport.phpt', '$test');

        self::assertSame('string', (string) $output);
    }

    /**
     * @return void
     */
    public function testReturnsInfoUsingClasslikeBeforeGenericSyntaxStartsWhenDeducingMethodCall(): void
    {
        $result = $this->deduceTypesFromExpression('MethodCallOnClassWithGenericSyntax.phpt', '$b');

        self::assertSame('string', (string) $result);
    }

    /**
     * @return void
     */
    public function testReturnsInfoUsingClasslikeBeforeGenericSyntaxStartsWhenDeducingPropertyFetch(): void
    {
        $result = $this->deduceTypesFromExpression('PropertyFetchOnClassWithGenericSyntax.phpt', '$b');

        self::assertSame('string', (string) $result);
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

        self::assertSame('\A\B', (string) $result);
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

        self::assertSame('(string | int | \Foo | \Bar)', (string) $result);
    }

    /**
     * @return void
     */
    public function testAnonymousClassDeduction(): void
    {
        $fileName = 'AnonymousClass.phpt';

        $result = $this->deduceTypesFromExpression($fileName, '$test');

        $filePath = $this->getFilePath($fileName);

        self::assertSame('\\anonymous_' . md5($this->normalizePath($filePath)) . '_19', (string) $result);
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

        self::assertSame('(\Throwable | \Exception)', (string) $result);
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

        self::assertSame('\DateTime', (string) $result);
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

        self::assertSame('\B\Bar', (string) $result);
    }

    // TODO: Enable and fix, see #234.
    // /**
    //  * @return void
    //  */
    // public function testMetaStaticMethodTypesWhenExpressionIsIncomplete(): void
    // {
    //     $result = $this->deduceTypesFromExpressionWithMeta(
    //         'MetaStaticMethodTypesMatchingFqcn.phpt',
    //         'MetaStaticMethodTypesMetaFile.phpt',
    //         '$foo->get("bar")->f',
    //         true
    //     );

    //     self::assertSame('\B\Bar', (string) $result);
    // }

    /**
     * @return void
     */
    public function testMetaStaticMethodTypesDoesNotTryToResolveDynamicMethodCall(): void
    {
        $result = $this->deduceTypesFromExpression(
            'MetaStaticMethodTypesDoesNotTryToResolveDynamicMethodCall.phpt',
            '$var'
        );

        self::assertSame('', (string) $result);
    }

    /**
     * @return void
     */
    public function testThrowsExceptionWhenFileIsNotInIndex(): void
    {
        $command = $this->container->get('deduceTypesJsonRpcQueueItemHandler');

        $this->expectException(FileNotFoundStorageException::class);

        $command->deduceTypes('DoesNotExist.phpt', 'Code', 'CodeWithExpression', new Position(0, 1), false);
    }

    /**
     * @param string $file
     * @param string $expression
     * @param bool   $ignoreLastElement
     *
     * @return TypeNode
     */
    private function deduceTypesFromExpression(
        string $file,
        string $expression,
        bool $ignoreLastElement = false
    ): TypeNode {
        $path = $this->getFilePath($file);

        $markerOffset = $this->getMarkerOffset($path, '<MARKER>');

        $this->indexTestFile($this->container, $path);

        $code = file_get_contents($path);

        return $this->container->get('expressionTypeDeducer')->deduce(
            new TextDocumentItem($this->normalizePath($path), $code),
            Position::createFromByteOffset($markerOffset, $code, PositionEncoding::VALUE),
            $expression,
            $ignoreLastElement
        );
    }

    /**
     * @param string $file
     * @param string $metaFile
     * @param string $expression
     * @param bool   $ignoreLastExpression
     *
     * @return TypeNode
     */
    private function deduceTypesFromExpressionWithMeta(
        string $file,
        string $metaFile,
        string $expression,
        bool $ignoreLastExpression = false
    ): TypeNode {
        $path = $this->getFilePath($file);
        $metaFilePath = __DIR__ . '/ExpressionTypeDeducerTest/' . $metaFile;

        $markerOffset = $this->getMarkerOffset($path, '<MARKER>');

        $this->indexTestFile($this->container, $metaFilePath);
        $this->indexTestFile($this->container, $path);

        $expressionTypeDeducer = $this->container->get('expressionTypeDeducer');

        $code = file_get_contents($path);

        return $this->container->get('expressionTypeDeducer')->deduce(
            new TextDocumentItem($path, $code),
            Position::createFromByteOffset($markerOffset, $code, PositionEncoding::VALUE),
            $expression,
            $ignoreLastExpression
        );
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function getFilePath(string $file): string
    {
        return 'file:///' . __DIR__ . '/ExpressionTypeDeducerTest/' . $file;
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
