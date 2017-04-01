<?php

namespace PhpIntegrator\Parsing\DocblockTypes;

/**
 * Parses docblock type specifications into more usable objects.
 *
 * @see https://phpdoc.org/docs/latest/references/phpdoc/types.html
 */
class DocblockTypeParser
{
    /**
     * @var string
     */
    private const ARRAY_TYPE_HINT_REGEX = '/^(.+)\[\]$/';

    /**
     * @var string
     */
    private const COMPOUND_TYPE_SPLITTER = '|';

    /**
     * @param string $specification
     *
     * @return DocblockType
     */
    public function parse(string $specification): DocblockType
    {
        $specification = $this->getSanitizedSpecification($specification);

        if ($this->isCompoundTypeSpecification($specification)) {
            return $this->parseCompooundTypeSpecification($specification);
        }

        return $this->parseNonCompoundTypeSpecification($specification);
    }

    /**
     * @param string $specification
     *
     * @return string
     */
    protected function getSanitizedSpecification(string $specification): string
    {
        $specification = trim($specification, ' |');

        if (!empty($specification) && $specification[0] === '(' && mb_substr($specification, -1) === ')') {
            $specification = mb_substr($specification, 1, -1);
        }

        return trim($specification, ' |');
    }

    /**
     * @param string $specification
     *
     * @return bool
     */
    protected function isCompoundTypeSpecification(string $specification): bool
    {
        $length = mb_strlen($specification);

        $paranthesesOpened = 0;
        $paranthesesClosed = 0;

        for ($i = 0; $i < $length; ++$i) {
            if ($specification[$i] === '(') {
                ++$paranthesesOpened;
            } elseif ($specification[$i] === ')') {
                ++$paranthesesClosed;
            }

            if ($paranthesesOpened === $paranthesesClosed && $specification[$i] === self::COMPOUND_TYPE_SPLITTER) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $specification
     *
     * @return CompoundDocblockType
     */
    protected function parseCompooundTypeSpecification(string $specification): CompoundDocblockType
    {
        $parts = [];

        $start = 0;
        $paranthesesOpened = 0;
        $paranthesesClosed = 0;
        $length = mb_strlen($specification);

        for ($i = 0; $i < $length; ++$i) {
            if ($specification[$i] === '(') {
                if ($i === 0) {
                    ++$start;
                } else {
                    ++$paranthesesOpened;
                }
            } elseif ($specification[$i] === ')') {
                if ($i !== $length - 1) {
                    ++$paranthesesClosed;
                }
            }

            if ($paranthesesOpened === $paranthesesClosed && $specification[$i] === self::COMPOUND_TYPE_SPLITTER) {
                $parts[] = $this->parse(mb_substr($specification, $start, $i - $start));
                $start = $i + 1;
            }
        }

        $parts[] = $this->parse(mb_substr($specification, $start, $i));

        return new CompoundDocblockType(...$parts);
    }

    /**
     * @param string $specification
     *
     * @return DocblockType
     */
    protected function parseNonCompoundTypeSpecification(string $specification): DocblockType
    {
        if ($specification === StringDocblockType::STRING_VALUE) {
            return new StringDocblockType();
        } elseif ($specification === IntDocblockType::STRING_VALUE ||
            $specification === IntDocblockType::STRING_VALUE_ALIAS
        ) {
            return new IntDocblockType();
        } elseif ($specification === BoolDocblockType::STRING_VALUE ||
            $specification === BoolDocblockType::STRING_VALUE_ALIAS
        ) {
            return new BoolDocblockType();
        } elseif ($specification === FloatDocblockType::STRING_VALUE ||
            $specification === FloatDocblockType::STRING_VALUE_ALIAS
        ) {
            return new FloatDocblockType();
        } elseif ($specification === ObjectDocblockType::STRING_VALUE) {
            return new ObjectDocblockType();
        } elseif ($specification === MixedDocblockType::STRING_VALUE) {
            return new MixedDocblockType();
        } elseif ($specification === ArrayDocblockType::STRING_VALUE) {
            return new ArrayDocblockType();
        } elseif ($specification === ResourceDocblockType::STRING_VALUE) {
            return new ResourceDocblockType();
        } elseif ($specification === VoidDocblockType::STRING_VALUE) {
            return new VoidDocblockType();
        } elseif ($specification === NullDocblockType::STRING_VALUE) {
            return new NullDocblockType();
        } elseif ($specification === CallableDocblockType::STRING_VALUE) {
            return new CallableDocblockType();
        } elseif ($specification === FalseDocblockType::STRING_VALUE) {
            return new FalseDocblockType();
        } elseif ($specification === TrueDocblockType::STRING_VALUE) {
            return new TrueDocblockType();
        } elseif ($specification === SelfDocblockType::STRING_VALUE) {
            return new SelfDocblockType();
        } elseif ($specification === StaticDocblockType::STRING_VALUE) {
            return new StaticDocblockType();
        } elseif ($specification === ThisDocblockType::STRING_VALUE) {
            return new ThisDocblockType();
        } elseif ($specification === IterableDocblockType::STRING_VALUE) {
            return new IterableDocblockType();
        } elseif (preg_match(self::ARRAY_TYPE_HINT_REGEX, $specification, $matches) === 1) {
            $valueType = $this->parse($matches[1]);

            return new SpecializedArrayDocblockType($valueType);
        }

        return new ClassDocblockType($specification);
    }
}
