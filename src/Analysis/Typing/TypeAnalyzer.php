<?php

namespace Serenata\Analysis\Typing;

use UnexpectedValueException;

use Serenata\Parsing\SpecialDocblockTypeIdentifierLiteral;

/**
 * Provides functionality for analyzing type names.
 *
 * @deprecated Determining if something is a special type or not can be done by using instanceof on AST nodes.
 */
final class TypeAnalyzer implements TypeNormalizerInterface
{
    /**
     * @var string
     */
    protected const ARRAY_TYPE_HINT_REGEX = '/^(.+)\[\]$/';

    /**
     * Indicates if a type is "special", i.e. it is not an actual class type, but rather a basic type (e.g. "int",
     * "bool", ...) or another special type (e.g. "$this", "false", ...).
     *
     * @param string $type
     *
     * @see https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md#keyword
     *
     * @return bool
     */
    public function isSpecialType(string $type): bool
    {
        $isReservedKeyword = in_array($type, SpecialDocblockTypeIdentifierLiteral::getValues(), true);

        return $isReservedKeyword || $this->isArraySyntaxTypeHint($type);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isClassType(string $type): bool
    {
        return !$this->isSpecialType($type);
    }

    /**
     * @inheritDoc
     */
    public function getNormalizedFqcn(string $fqcn): string
    {
        if ($fqcn !== '' && $fqcn[0] !== '\\') {
            return '\\' . $fqcn;
        }

        return $fqcn;
    }

    /**
     * @param string $type
     *
     * @return bool
     *
     * @deprecated Cannot handle parantheses or extended docblock types. Use the docblock type parser service instead.
     */
    public function isArraySyntaxTypeHint(string $type): bool
    {
        return (preg_match(self::ARRAY_TYPE_HINT_REGEX, $type) === 1);
    }

    /**
     * Takes an actual (single) docblock type that contains self and replaces it with the designated type.
     *
     * @param string $docblockType
     * @param string $newType
     *
     * @example "self" with new type "Foo" becomes "Foo".
     * @example "self[]" with new type "\A\B" becomes "\A\B[]".
     *
     * @return string
     */
    public function interchangeSelfWithActualType(string $docblockType, string $newType): string
    {
        return $this->interchangeType($docblockType, SpecialDocblockTypeIdentifierLiteral::SELF_, $newType);
    }

    /**
     * Takes an actual (single) docblock type that contains static and replaces it with the designated type.
     *
     * @param string $docblockType
     * @param string $newType
     *
     * @example "static" with new type "Foo" becomes "Foo".
     * @example "static[]" with new type "\A\B" becomes "\A\B[]".
     *
     * @return string
     */
    public function interchangeStaticWithActualType(string $docblockType, string $newType): string
    {
        return $this->interchangeType($docblockType, SpecialDocblockTypeIdentifierLiteral::STATIC_, $newType);
    }

    /**
     * Takes an actual (single) docblock type that contains self and replaces it with the designated type.
     *
     * @param string $docblockType
     * @param string $newType
     *
     * @example "self" with new type "Foo" becomes "Foo".
     * @example "self[]" with new type "\A\B" becomes "\A\B[]".
     *
     * @return string
     */
    public function interchangeThisWithActualType(string $docblockType, string $newType): string
    {
        return $this->interchangeType($docblockType, SpecialDocblockTypeIdentifierLiteral::THIS_, $newType);
    }

    /**
     * Takes an actual (single) docblock type and replaces it with the designated type.
     *
     * @param string $docblockType
     * @param string $oldType
     * @param string $newType
     *
     * @return string
     */
    private function interchangeType(string $docblockType, string $oldType, string $newType): string
    {
        if ($this->isArraySyntaxTypeHint($docblockType)) {
            $valueType = $this->getValueTypeFromArraySyntaxTypeHint($docblockType);

            if ($valueType === $oldType) {
                return $newType . '[]';
            }
        } elseif ($docblockType === $oldType) {
            return $newType;
        }

        return $docblockType;
    }

    /**
     * @param string $type
     *
     * @return string
     *
     * @deprecated Cannot handle parantheses or extended docblock types. Use the docblock type parser service instead.
     */
    private function getValueTypeFromArraySyntaxTypeHint(string $type): string
    {
        $matches = [];

        if (preg_match(self::ARRAY_TYPE_HINT_REGEX, $type, $matches) === 1) {
            return $matches[1];
        }

        throw new UnexpectedValueException('"' . $type . '" is not an array type hint');
    }
}
