<?php

namespace Serenata\Parsing;

use LogicException;

use PhpParser\Node;
use PhpParser\Error;
use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ErrorHandler;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitorAbstract;

use Serenata\Parsing\Node\Expr;

use Serenata\Parsing\Node\Keyword\Self_;
use Serenata\Parsing\Node\Keyword\Parent_;
use Serenata\Parsing\Node\Keyword\Static_;

/**
 * Parses partial (incomplete) PHP code.
 *
 * This class can parse PHP code that is incomplete (and thus erroneous), which is only partially supported by
 * php-parser. This is necessary for being able to deal with incomplete expressions such as "$this->" to see what the
 * type of the expression is. This information can in turn be used by client functionality such as autocompletion.
 */
final class PartialParser implements Parser
{
    /**
     * @var Parser|null
     */
    private $strictParser;

    /**
     * @var ParserFactory
     */
    private $parserFactory;

    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @param ParserFactory $parserFactory
     * @param Lexer         $lexer
     */
    public function __construct(ParserFactory $parserFactory, Lexer $lexer)
    {
        $this->parserFactory = $parserFactory;
        $this->lexer = $lexer;
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

        $correctedExpression = $this->getNormalizedCode($code);

        $isValid = function (?array $result): bool {
            return $result !== null && $result !== [];
        };

        $list = $this->tryParse($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithKeywordCorrection($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithTrailingSemicolonCorrection($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithHeredocTerminationCorrection($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithFunctionTerminationCorrection($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithFunctionMissingArgumentCorrection($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithTernaryOperatorTerminationCorrection($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithDummyInsertion($correctedExpression);
        $list = $isValid($list) ? $list : $this->tryParseWithDoubleArrowFix($correctedExpression);

        return $list;
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

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParseWithKeywordCorrection(string $code): ?array
    {
        $expectedOffset = mb_strlen($code) - mb_strlen('self');

        if (mb_strrpos($code, 'self') === $expectedOffset) {
            $node = new Self_();
            $node->setAttribute('startFilePos', $expectedOffset);

            return [$node];
        }

        $expectedOffset = mb_strlen($code) - mb_strlen('static');

        if (mb_strrpos($code, 'static') === $expectedOffset) {
            $node = new Static_();
            $node->setAttribute('startFilePos', $expectedOffset);

            return [$node];
        }

        $expectedOffset = mb_strlen($code) - mb_strlen('parent');

        if (mb_strrpos($code, 'parent') === $expectedOffset) {
            $node = new Parent_();
            $node->setAttribute('startFilePos', $expectedOffset);

            return [$node];
        }

        return null;
    }

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParseWithTrailingSemicolonCorrection(string $code): ?array
    {
        return $this->tryParse($code . ';');
    }

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParseWithHeredocTerminationCorrection(string $code): ?array
    {
        return $this->tryParse($code . ";\n"); // Heredocs need to be suffixed by a semicolon and a newline.
    }

    /**
     * @param string $code
     *
     * @return array|null
     */
    private function tryParseWithFunctionTerminationCorrection(string $code): ?array
    {
        return $this->tryParse($code . ");");
    }

    /**
     * @param string $code
     *
     * @return array|null
     */
    private function tryParseWithFunctionMissingArgumentCorrection(string $code): ?array
    {
        $dummyName = '____DUMMY____';

        $nodes = $this->tryParse($code . " {$dummyName});");

        if ($nodes === null || count($nodes) === 0) {
            return $nodes;
        }

        $node = $nodes[count($nodes) - 1];

        if ($node instanceof Node\Stmt\Expression) {
            if ($node->expr instanceof Node\Expr\MethodCall ||
                $node->expr instanceof Node\Expr\FuncCall ||
                $node->expr instanceof Node\Expr\StaticCall ||
                $node->expr instanceof Node\Expr\New_
            ) {
                foreach ($node->expr->args as $i => $arg) {
                    if ($arg->value instanceof Node\Expr\ConstFetch && $arg->value->name->toString() === $dummyName) {
                        array_splice($node->expr->args, $i, $i+1);

                        break;
                    }
                }
            }
        }

        return $nodes;
    }

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParseWithTernaryOperatorTerminationCorrection(string $code): ?array
    {
        $dummyName = '____DUMMY____';

        $nodes = $this->tryParse($code . ": {$dummyName};");

        if ($nodes === null || count($nodes) === 0) {
            return $nodes;
        }

        $node = $nodes[count($nodes) - 1];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($dummyName) extends NodeVisitorAbstract {
            private $dummyName;

            public function __construct(string $dummyName)
            {
                $this->dummyName = $dummyName;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\Expr\Ternary) {
                    if ($node->else instanceof Node\Expr\ConstFetch &&
                        $node->else->name->toString() === $this->dummyName
                    ) {
                        $node->else = new Expr\Dummy();
                    }
                }

                return null;
            }
        });

        $traverser->traverse($nodes);

        return $nodes;
    }

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParseWithDummyInsertion(string $code): ?array
    {
        $removeDummy = false;
        $dummyName = '____DUMMY____';

        $nodes = $this->tryParse($code . $dummyName . ';');

        if ($nodes === null || count($nodes) === 0) {
            return null;
        }

        $node = $nodes[count($nodes) - 1];

        if ($node instanceof Node\Stmt\Expression) {
            if ($node->expr instanceof Node\Expr\ClassConstFetch || $node->expr instanceof Node\Expr\PropertyFetch) {
                if ($node->expr->name->name === $dummyName) {
                    $node->expr->name->name = '';
                }
            }
        }

        return $nodes;
    }

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParseWithDoubleArrowFix(string $code): ?array
    {
        $removeDummy = false;
        $dummyName = '____DUMMY____';

        // Pretend multiple arrows are actually valid by inserting something in between them, then treat as ordinary
        // method call.
        $fixedCode = $code;

        while (mb_strpos($fixedCode, '->->') !== false) {
            $fixedCode = str_replace('->->', '->' . $dummyName . '->', $fixedCode);
        }

        $nodes = $this->tryParseWithDummyInsertion($fixedCode);

        if ($nodes === null || count($nodes) === 0) {
            return null;
        }

        $node = $nodes[count($nodes) - 1];

        $removeDummies = function (Node $node) use ($dummyName, &$removeDummies): void {
            if ($node instanceof Node\Expr\PropertyFetch) {
                if ($node->var instanceof Node\Expr\ClassConstFetch ||
                    $node->var instanceof Node\Expr\PropertyFetch
                ) {
                    if ($node->var->name instanceof Node\Identifier &&
                        $node->var->name->name === $dummyName
                    ) {
                        $node->var->name->name = '';

                        if ($node->var instanceof Node\Expr\PropertyFetch) {
                            $removeDummies($node->var->var);
                        }
                    }
                }

                $removeDummies($node->var);
            }
        };

        if ($node instanceof Node\Stmt\Expression) {
            $removeDummies($node->expr);
        }

        return $nodes;
    }

    /**
     * @param string $code
     *
     * @return Node[]|null
     */
    private function tryParse(string $code): ?array
    {
        try {
            return $this->getStrictParser()->parse($code);
        } catch (Error $e) {
            return null;
        }
    }

    /**
     * @return Parser
     */
    private function getStrictParser(): Parser
    {
        if ($this->strictParser === null) {
            $this->strictParser = $this->parserFactory->create(ParserFactory::PREFER_PHP7, $this->lexer);
        }

        return $this->strictParser;
    }
}
