<?php

namespace Serenata\Parsing;

use LogicException;

use PhpParser\Node;
use PhpParser\Error;
use PhpParser\ErrorHandler;

use PhpParser\Node\Stmt\Nop;

use PhpParser\Parser;

/**
 * Parser that attempts to parse the last expression in a string of code.
 */
final class LastExpressionParser implements Parser
{
    /**
     * @var Parser
     */
    private $delegate;

    /**
     * @var ParserTokenHelper
     */
    private $parserTokenHelper;

    /**
     * @param Parser                $delegate
     * @param ParserTokenHelper     $parserTokenHelper
     */
    public function __construct(Parser $delegate, ParserTokenHelper $parserTokenHelper)
    {
        $this->delegate = $delegate;
        $this->parserTokenHelper = $parserTokenHelper;
    }

    /**
     * @inheritDoc
     */
    public function parse(string $code, ?ErrorHandler $errorHandler = null)
    {
        if ($errorHandler !== null) {
            throw new LogicException(
                'Error handling is not supported as error recovery will be attempted automatically'
            );
        }

        $code = $this->getNormalizedCode($code);
        $boundary = $this->getStartOfExpression($code);

        $expression = substr($code, $boundary);
        $expression = trim($expression);

        if ($expression !== '') {
            $nodes = $this->delegate->parse($expression, $errorHandler);
        } else {
            $nodes = [new Nop()];
        }

        if ($nodes === null || count($nodes) === 0) {
            throw new Error(
                'Could not parse the code, even after attempting corrections. The following snippet failed: ' .
                '❰' . $code . '❱, the expression was ❰' . $expression . '❱'
            );
        } elseif (count($nodes) > 1) {
            throw new Error(
                'Parsing succeeded, but more than one node was returned for a single expression for the following ' .
                'snippet ❰' . $code . '❱, the expression was ❰' . $expression . '❱'
            );
        }

        return $nodes;
    }

    /**
     * Retrieves the last node (i.e. expression, statement, ...) at the specified location.
     *
     * This will also attempt to deal with incomplete expressions and statements.
     *
     * @param string   $source
     * @param int|null $offset
     *
     * @throws Error
     *
     * @return Node|null
     */
    public function getLastNodeAt(string $source, ?int $offset = null): ?Node
    {
        if ($offset !== null) {
            $source = substr($source, 0, $offset);
        }

        $nodes = $this->parse($source);

        if ($nodes === null) {
            return null;
        }

        return array_shift($nodes);
    }

    /**
     * Retrieves the start of the expression (as byte offset) that ends at the end of the specified source code string.
     *
     * @param string $code
     *
     * @return int
     */
    private function getStartOfExpression(string $code): int
    {
        if ($code === '') {
            return 0;
        }

        $tokens = @token_get_all($code);

        $heredocStart = $this->tryGetStartOfHeredocExpression($code, $tokens);

        if ($heredocStart !== null) {
            return $heredocStart;
        }

        return $this->getStartOfOtherExpression($code, $tokens);
    }

    /**
     * @param string  $code
     * @param mixed[] $tokens
     *
     * @return int|null
     */
    private function tryGetStartOfHeredocExpression(string $code, array $tokens): ?int
    {
        if ($code === '') {
            return null;
        }

        $i = 0;
        $hereDocsOpened = 0;
        $hereDocsClosed = 0;
        $tokenStartOffset = strlen($code);
        $currentTokenIndex = count($tokens);
        $busyWithTermination = false;
        $isWalkingHeredocStart = false;
        $isWalkingHeredocEnd = false;

        /** @var array<string,mixed> $token token will always be set below, but PHPStan doesn't see that. */
        $token = [];

        // Heredocs don't always have a termination token, catch those early as heredocs can contain interpolated
        // expressions, which must then be ignored.
        for ($i = strlen($code) - 1; $i >= 0; --$i) {
            if ($i < $tokenStartOffset) {
                $token = $tokens[--$currentTokenIndex];

                $tokenString = is_array($token) ? $token[1] : $token;
                $tokenStartOffset = ($i + 1) - strlen($tokenString);

                $token = [
                    'type' => is_array($token) ? $token[0] : null,
                    'text' => $tokenString,
                ];
            }

            if ($busyWithTermination) {
                if ($token['type'] !== T_START_HEREDOC) {
                    return $i + 1;
                }
            } elseif ($token['type'] === T_START_HEREDOC) {
                if (!$isWalkingHeredocStart) {
                    ++$hereDocsOpened;
                    $isWalkingHeredocStart = true;

                    if ($hereDocsOpened > $hereDocsClosed) {
                        $busyWithTermination = true;
                    }
                }
            } elseif ($token['type'] === T_END_HEREDOC) {
                if (!$isWalkingHeredocEnd) {
                    ++$hereDocsClosed;
                    $isWalkingHeredocEnd = true;
                }
            }

            if ($isWalkingHeredocStart && $token['type'] !== T_START_HEREDOC) {
                $isWalkingHeredocStart = false;
            }

            if ($isWalkingHeredocEnd && $token['type'] !== T_END_HEREDOC) {
                $isWalkingHeredocEnd = false;
            }
        }

        return null;
    }

    /**
     * @param string  $code
     * @param mixed[] $tokens
     *
     * @return int
     */
    private function getStartOfOtherExpression(string $code, array $tokens): int
    {
        if ($code === '') {
            return 0;
        }

        $i = 0;
        $parenthesesOpened = 0;
        $parenthesesClosed = 0;
        $squareBracketsOpened = 0;
        $squareBracketsClosed = 0;
        $squiggleBracketsOpened = 0;
        $squiggleBracketsClosed = 0;

        $isInDoubleQuotedString = false;
        $startedStaticClassName = false;

        $skippableTokens = $this->parserTokenHelper->getSkippableTokens();
        $castBoundaryTokens = $this->parserTokenHelper->getCastBoundaryTokens();
        $expressionBoundaryTokens = $this->parserTokenHelper->getExpressionBoundaryTokens();
        $expressionBoundaryCharacters = $this->getExpressionBoundaryCharacters();

        $tokenStartOffset = strlen($code);
        $currentTokenIndex = count($tokens);
        $tokenInfoMap = $this->generateTokenInfoMap($code, $tokens);

        /** @var array<string,mixed> $token token will always be set below, but PHPStan doesn't see that. */
        $token = [];

        for ($i = strlen($code) - 1; $i >= 0; --$i) {
            if ($i < $tokenStartOffset) {
                $token = $tokenInfoMap[$i];
                $currentTokenIndex = $token['tokenIndex'];
                $tokenStartOffset = $token['startOffset'];
                $tokenString = $token['text'];
            }

            if (in_array($token['type'], $skippableTokens, true)) {
                // Do nothing, we just keep parsing. (These can occur inside call stacks.)
            } elseif ($code[$i] === '"') {
                if (!$isInDoubleQuotedString) {
                    $isInDoubleQuotedString = true;
                } else {
                    $isInDoubleQuotedString = false;
                }
            } elseif (!$isInDoubleQuotedString) {
                if ($code[$i] === '(') {
                    ++$parenthesesOpened;

                    // Ticket #164 - We're walking backwards, if we find an opening paranthesis that hasn't been closed
                    // anywhere, we know we must stop.
                    if ($parenthesesOpened > $parenthesesClosed) {
                        return ++$i;
                    }
                } elseif ($code[$i] === ')') {
                    if ($this->isParenthesisFollowedByImpossibleCharacter($code, $i, $tokenInfoMap) ||
                        in_array($token['type'], $castBoundaryTokens, true)
                    ) {
                        return ++$i;
                    }

                    ++$parenthesesClosed;
                } elseif ($code[$i] === '[') {
                    ++$squareBracketsOpened;

                    // Same as above.
                    if ($squareBracketsOpened > $squareBracketsClosed) {
                        return ++$i;
                    }
                } elseif ($code[$i] === ']') {
                    if ($this->isSquareBracketFollowedByImpossibleCharacter($code, $i, $tokenInfoMap)) {
                        return ++$i;
                    }

                    ++$squareBracketsClosed;
                } elseif ($code[$i] === '{') {
                    ++$squiggleBracketsOpened;

                    // Same as above.
                    if ($squiggleBracketsOpened > $squiggleBracketsClosed) {
                        return ++$i;
                    }
                } elseif ($code[$i] === '}') {
                    ++$squiggleBracketsClosed;

                    if ($parenthesesOpened === $parenthesesClosed && $squareBracketsOpened === $squareBracketsClosed) {
                        $nextToken = $currentTokenIndex > 0 ? $tokens[$currentTokenIndex - 1] : null;
                        $nextTokenType = is_array($nextToken) ? $nextToken[0] : null;

                        // Subscopes can only exist when e.g. a closure is embedded as an argument to a function call,
                        // in which case they will be inside parentheses or brackets. If we find a subscope outside
                        // these symbols, it means we've moved beyond the call stack to e.g. the end of an if statement.
                        if ($nextTokenType !== T_VARIABLE) {
                            return ++$i;
                        }
                    }
                } elseif ($parenthesesOpened === $parenthesesClosed &&
                    $squareBracketsOpened === $squareBracketsClosed &&
                    $squiggleBracketsOpened === $squiggleBracketsClosed
                ) {
                    // NOTE: We may have entered a closure.
                    if (in_array($token['type'], $expressionBoundaryTokens, true)) {
                        $nextToken = $currentTokenIndex > 0 ? $tokens[$currentTokenIndex - 1] : null;
                        $nextTokenType = is_array($nextToken) ? $nextToken[0] : null;

                        if ($nextTokenType !== T_DOUBLE_COLON) {
                            return ++$i;
                        }
                    } elseif (in_array($code[$i], $expressionBoundaryCharacters, true) && $token['type'] === null) {
                        return ++$i;
                    } elseif ($code[$i] === ':' && $token['type'] !== T_DOUBLE_COLON) {
                        return ++$i;
                    } elseif ($token['type'] === T_DOUBLE_COLON) {
                        // For static class names and things like the self and parent keywords, we won't know when to
                        // stop. These always appear the start of the call stack, so we know we can stop if we find
                        // them.
                        $startedStaticClassName = true;
                    }
                }
            }

            if ($startedStaticClassName &&
                !in_array($token['type'], [T_DOUBLE_COLON, T_STRING, T_NS_SEPARATOR, T_STATIC], true)
            ) {
                return ++$i;
            }
        }

        return $i;
    }

    /**
     * @param string                         $code
     * @param int                            $i
     * @param array<int,array<string,mixed>> $tokenInfoMap
     *
     * @return bool
     */
    private function isParenthesisFollowedByImpossibleCharacter(string $code, int $i, array $tokenInfoMap): bool
    {
        $skippableTokens = $this->parserTokenHelper->getSkippableTokens();
        $expressionBoundaryTokens = $this->parserTokenHelper->getExpressionBoundaryTokens();
        $expressionBoundaryCharacters = $this->getExpressionBoundaryCharacters();

        $nextNonWhitespace = null;

        for ($j = $i + 1; $j < count($tokenInfoMap); ++$j) {
            if (!in_array($tokenInfoMap[$j]['type'], $skippableTokens, true)) {
                $nextNonWhitespace = $j;

                break;
            }
        }

        if ($nextNonWhitespace !== null) {
            // Alphanumeric, variable start, ...
            if (!in_array($code[$nextNonWhitespace], $expressionBoundaryCharacters, true) &&
                !in_array($tokenInfoMap[$nextNonWhitespace]['type'], $expressionBoundaryTokens, true) &&
                $code[$nextNonWhitespace] !== '{' &&
                $code[$nextNonWhitespace] !== '}' &&
                $code[$nextNonWhitespace] !== '[' &&
                $code[$nextNonWhitespace] !== ']' &&
                $code[$nextNonWhitespace] !== '(' &&
                $code[$nextNonWhitespace] !== ')' &&
                $code[$nextNonWhitespace] !== ':'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string                         $code
     * @param int                            $i
     * @param array<int,array<string,mixed>> $tokenInfoMap
     *
     * @return bool
     */
    private function isSquareBracketFollowedByImpossibleCharacter(string $code, int $i, array $tokenInfoMap): bool
    {
        return $this->isParenthesisFollowedByImpossibleCharacter($code, $i, $tokenInfoMap);
    }

    /**
     * Retrieves characters that include operators that are, for some reason, not token types.
     *
     * @return string[]
     */
    private function getExpressionBoundaryCharacters(): array
    {
        return [
            '.', ',', '?', ';', '=', '+', '-', '*', '/', '<', '>', '%', '|', '&', '^', '~', '!', '@',
        ];
    }

    /**
     * @param string  $code
     * @param mixed[] $tokens
     *
     * @return array<int,array<string,mixed>>
     */
    private function generateTokenInfoMap(string $code, array $tokens): array
    {
        if ($code === '') {
            return [];
        }

        $tokenString = null;
        $tokenStartOffset = strlen($code);
        $currentTokenIndex = count($tokens);

        $tokenInfoMap = [];

        /** @var array<string,mixed> $token token will always be set below, but PHPStan doesn't see that. */
        $token = [];

        for ($i = strlen($code) - 1; $i >= 0; --$i) {
            if ($i < $tokenStartOffset) {
                $token = $tokens[--$currentTokenIndex];

                $tokenString = is_array($token) ? $token[1] : $token;
                $tokenStartOffset = ($i + 1) - strlen($tokenString);
            }

            $tokenInfoMap[$i] = [
                'type' => is_array($token) ? $token[0] : null,
                'text' => $tokenString,
                'startOffset' => $tokenStartOffset,
                'tokenIndex' => $currentTokenIndex,
            ];
        }

        return $tokenInfoMap;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function getNormalizedCode(string $code): string
    {
        if (mb_substr(trim($code), 0, 5) !== '<?php') {
            return '<?php ' . $code;
        }

        return $code;
    }
}
