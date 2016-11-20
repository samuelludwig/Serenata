<?php

namespace PhpIntegrator\Analysis\Visiting;

use PhpIntegrator\Parsing\DocblockParser;

use PhpIntegrator\Utility\NodeHelpers;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that walks to a specific position, building a list of information about variables and their possible and
 * guaranteed types.
 */
class TypeQueryingVisitor extends NodeVisitorAbstract
{
    /**
     * @var int
     */
    protected $position;

    /**
     * @var DocblockParser
     */
    protected $docblockParser;

    /**
     * @var VariableTypeInfoMap
     */
    protected $variableTypeInfoMap;

    /**
     * Constructor.
     *
     * @param DocblockParser $docblockParser
     * @param int            $position
     */
    public function __construct(DocblockParser $docblockParser, $position)
    {
        $this->docblockParser = $docblockParser;
        $this->position = $position;
        $this->variableTypeInfoMap = new VariableTypeInfoMap();
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        $startFilePos = $node->getAttribute('startFilePos');

        if ($startFilePos >= $this->position) {
            if ($startFilePos == $this->position) {
                // We won't analyze this node anymore (it falls outside the position and can cause infinite recursion
                // otherwise), but php-parser matches each docblock with the next node. That docblock might still
                // contain a type override annotation we need to parse.
                $this->parseNodeDocblock($node);
            }

            // We've gone beyond the requested position, there is nothing here that can still be relevant anymore.
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        $this->parseNodeDocblock($node);

        if ($node instanceof Node\Stmt\Catch_) {
            $this->parseCatch($node);
        } elseif ($node instanceof Node\Stmt\If_ ||
            $node instanceof Node\Stmt\ElseIf_ ||
            $node instanceof Node\Expr\Ternary
        ) {
            $this->parseConditional($node);
        } elseif ($node instanceof Node\Expr\Assign) {
            $this->parseAssignment($node);
        } elseif ($node instanceof Node\Stmt\Foreach_) {
            $this->parseForeach($node);
        }

        $this->checkForScopeChange($node);
    }

    /**
     * @param Node\Stmt\Catch_ $node
     */
    protected function parseCatch(Node\Stmt\Catch_ $node)
    {
        $this->variableTypeInfoMap->setBestMatch($node->var, $node->type);
    }

    /**
     * @param NodeAbstract $node
     */
    protected function parseConditional(NodeAbstract $node)
    {
        // There can be conditional expressions inside the current scope (think variables assigned to a ternary
        // expression). In that case we don't want to actually look at the condition for type deduction unless
        // we're inside the scope of that conditional.
        if ($this->position < $node->getAttribute('startFilePos') ||
            $this->position > $node->getAttribute('endFilePos')
        ) {
            return;
        }

        $typeData = $this->parseCondition($node->cond);

        foreach ($typeData as $variable => $newConditionalTypes) {
            $info = $this->variableTypeInfoMap->get($variable);

            foreach ($newConditionalTypes as $type => $possibility) {
                $info->setPossibilityOfType($type, $possibility);
            }
        }
    }

    /**
     * @param Node\Expr\Assign $node
     */
    protected function parseAssignment(Node\Expr\Assign $node)
    {
        if ($node->getAttribute('endFilePos') > $this->position) {
            return;
        } elseif (!$node->var instanceof Node\Expr\Variable) {
            return;
        }

        $this->variableTypeInfoMap->setBestMatch((string) $node->var->name, $node);
    }

    /**
     * @param Node\Stmt\Foreach_ $node
     */
    protected function parseForeach(Node\Stmt\Foreach_ $node)
    {
        if (!$node->valueVar instanceof Node\Expr\List_) {
            $this->variableTypeInfoMap->setBestMatch($node->valueVar->name, $node);
        }
    }

    /**
     * @param Node $node
     */
    protected function checkForScopeChange(Node $node)
    {
        if ($node->getAttribute('startFilePos') > $this->position ||
            $node->getAttribute('endFilePos') < $this->position
        ) {
            return;
        }

        if ($node instanceof Node\Stmt\ClassLike) {
            $this->variableTypeInfoMap->clear();
            $this->variableTypeInfoMap->setBestMatch('this', $node);
        } elseif ($node instanceof Node\FunctionLike) {
            $variablesOutsideCurrentScope = ['this'];

            // If a variable is in a use() statement of a closure, we can't reset the state as we still need to
            // examine the parent scope of the closure where the variable is defined.
            if ($node instanceof Node\Expr\Closure) {
                foreach ($node->uses as $closureUse) {
                    $variablesOutsideCurrentScope[] = $closureUse->var;
                }
            }

            $this->variableTypeInfoMap->removeAllExcept($variablesOutsideCurrentScope);

            foreach ($node->getParams() as $param) {
                $this->variableTypeInfoMap->setBestMatch($param->name, $node);
            }
        }
    }

    /**
     * @param Node\Expr $node
     *
     * @return array
     */
    protected function parseCondition(Node\Expr $node)
    {
        $types = [];

        if (
            $node instanceof Node\Expr\BinaryOp\BitwiseAnd ||
            $node instanceof Node\Expr\BinaryOp\BitwiseOr ||
            $node instanceof Node\Expr\BinaryOp\BitwiseXor ||
            $node instanceof Node\Expr\BinaryOp\BooleanAnd ||
            $node instanceof Node\Expr\BinaryOp\BooleanOr ||
            $node instanceof Node\Expr\BinaryOp\LogicalAnd ||
            $node instanceof Node\Expr\BinaryOp\LogicalOr ||
            $node instanceof Node\Expr\BinaryOp\LogicalXor
        ) {
            $leftTypes = $this->parseCondition($node->left);
            $rightTypes = $this->parseCondition($node->right);

            $types = $leftTypes;

            foreach ($rightTypes as $variable => $conditionalTypes) {
                foreach ($conditionalTypes as $conditionalType => $possibility) {
                    $types[$variable][$conditionalType] = $possibility;
                }
            }
        } elseif (
            $node instanceof Node\Expr\BinaryOp\Equal ||
            $node instanceof Node\Expr\BinaryOp\Identical
        ) {
            if ($node->left instanceof Node\Expr\Variable) {
                if ($node->right instanceof Node\Expr\ConstFetch && $node->right->name->toString() === 'null') {
                    $types[$node->left->name]['null'] = TypePossibility::TYPE_GUARANTEED;
                }
            } elseif ($node->right instanceof Node\Expr\Variable) {
                if ($node->left instanceof Node\Expr\ConstFetch && $node->left->name->toString() === 'null') {
                    $types[$node->right->name]['null'] = TypePossibility::TYPE_GUARANTEED;
                }
            }
        } elseif (
            $node instanceof Node\Expr\BinaryOp\NotEqual ||
            $node instanceof Node\Expr\BinaryOp\NotIdentical
        ) {
            if ($node->left instanceof Node\Expr\Variable) {
                if ($node->right instanceof Node\Expr\ConstFetch && $node->right->name->toString() === 'null') {
                    $types[$node->left->name]['null'] = TypePossibility::TYPE_IMPOSSIBLE;
                }
            } elseif ($node->right instanceof Node\Expr\Variable) {
                if ($node->left instanceof Node\Expr\ConstFetch && $node->left->name->toString() === 'null') {
                    $types[$node->right->name]['null'] = TypePossibility::TYPE_IMPOSSIBLE;
                }
            }
        } elseif ($node instanceof Node\Expr\BooleanNot) {
            if ($node->expr instanceof Node\Expr\Variable) {
                $types[$node->expr->name]['int']    = TypePossibility::TYPE_POSSIBLE; // 0
                $types[$node->expr->name]['string'] = TypePossibility::TYPE_POSSIBLE; // ''
                $types[$node->expr->name]['float']  = TypePossibility::TYPE_POSSIBLE; // 0.0
                $types[$node->expr->name]['array']  = TypePossibility::TYPE_POSSIBLE; // []
                $types[$node->expr->name]['null']   = TypePossibility::TYPE_POSSIBLE; // null
            } else {
                $subTypes = $this->parseCondition($node->expr);

                // Reverse the possiblity of the types.
                $reversedTypes = [];

                foreach ($subTypes as $variable => $typeData) {
                    foreach ($typeData as $subType => $possibility) {
                        if ($possibility === TypePossibility::TYPE_GUARANTEED) {
                            $reversedTypes[$variable][$subType] = TypePossibility::TYPE_IMPOSSIBLE;
                        } elseif ($possibility === TypePossibility::TYPE_IMPOSSIBLE) {
                            $reversedTypes[$variable][$subType] = TypePossibility::TYPE_GUARANTEED;
                        } elseif ($possibility === TypePossibility::TYPE_POSSIBLE) {
                            // Possible types are effectively negated and disappear.
                        }
                    }
                }

                $types = array_merge($types, $reversedTypes);
            }
        } elseif ($node instanceof Node\Expr\Variable) {
            $types[$node->name]['null'] = TypePossibility::TYPE_IMPOSSIBLE;
        } elseif ($node instanceof Node\Expr\Instanceof_) {
            if ($node->expr instanceof Node\Expr\Variable) {
                if ($node->class instanceof Node\Name) {
                    $types[$node->expr->name][NodeHelpers::fetchClassName($node->class)] = TypePossibility::TYPE_GUARANTEED;
                } else {
                    // This is an expression, we could fetch its return type, but that still won't tell us what
                    // the actual class is, so it's useless at the moment.
                }
            }
        } elseif ($node instanceof Node\Expr\FuncCall) {
            if ($node->name instanceof Node\Name) {
                $variableHandlingFunctionTypeMap = [
                    'is_array'    => ['array'],
                    'is_bool'     => ['bool'],
                    'is_callable' => ['callable'],
                    'is_double'   => ['float'],
                    'is_float'    => ['float'],
                    'is_int'      => ['int'],
                    'is_integer'  => ['int'],
                    'is_long'     => ['int'],
                    'is_null'     => ['null'],
                    'is_numeric'  => ['int', 'float', 'string'],
                    'is_object'   => ['object'],
                    'is_real'     => ['float'],
                    'is_resource' => ['resource'],
                    'is_scalar'   => ['int', 'float', 'string', 'bool'],
                    'is_string'   => ['string']
                ];

                if (isset($variableHandlingFunctionTypeMap[$node->name->toString()])) {
                    if (
                        !empty($node->args) &&
                        !$node->args[0]->unpack &&
                        $node->args[0]->value instanceof Node\Expr\Variable
                    ) {
                        $guaranteedTypes = $variableHandlingFunctionTypeMap[$node->name->toString()];

                        foreach ($guaranteedTypes as $guaranteedType) {
                            $types[$node->args[0]->value->name][$guaranteedType] = TypePossibility::TYPE_GUARANTEED;
                        }
                    }
                }
            }
        }

        return $types;
    }

    /**
     * @param Node $node
     */
    protected function parseNodeDocblock(Node $node)
    {
        $docblock = $node->getDocComment();

        if (!$docblock) {
            return;
        }

        // Check for a reverse type annotation /** @var $someVar FooType */. These aren't correct in the sense that
        // they aren't consistent with the standard syntax "@var <type> <name>", but they are still used by some IDE's.
        // For this reason we support them, but only their most elementary form.
        $classRegexPart = "?:\\\\?[a-zA-Z_][a-zA-Z0-9_]*(?:\\\\[a-zA-Z_][a-zA-Z0-9_]*)*";
        $reverseRegexTypeAnnotation = "/\/\*\*\s*@var\s+\\\$([A-Za-z0-9_])\s+(({$classRegexPart}(?:\[\])?))\s*(\s.*)?\*\//";

        if (preg_match($reverseRegexTypeAnnotation, $docblock, $matches) === 1) {
            $this->variableTypeInfoMap->setBestTypeOverrideMatch(
                $matches[1],
                $matches[2],
                $node->getLine()
            );
        } else {
            $docblockData = $this->docblockParser->parse((string) $docblock, [
                DocblockParser::VAR_TYPE
            ], null);

            foreach ($docblockData['var'] as $variableName => $data) {
                if ($data['type']) {
                    $this->variableTypeInfoMap->setBestTypeOverrideMatch(
                        mb_substr($variableName, 1),
                        $data['type'],
                        $node->getLine()
                    );
                }
            }
        }
    }

    /**
     * @return VariableTypeInfoMap
     */
    public function getVariableTypeInfoMap()
    {
        return $this->variableTypeInfoMap;
    }
}
