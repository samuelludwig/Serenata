<?php

namespace PhpIntegrator\Linting;

use PhpIntegrator\Analysis\ClasslikeInfoBuilder;

use PhpIntegrator\Analysis\Typing\TypeAnalyzer;

use PhpIntegrator\Analysis\Typing\Deduction\NodeTypeDeducerInterface;

use PhpIntegrator\Analysis\Visiting\MemberUsageFetchingVisitor;

/**
 * Looks for unknown member names.
 */
class UnknownMemberAnalyzer implements AnalyzerInterface
{
    /**
     * @var MemberUsageFetchingVisitor
     */
    protected $methodUsageFetchingVisitor;

    /**
     * @param NodeTypeDeducerInterface $nodeTypeDeducer
     * @param ClasslikeInfoBuilder     $classlikeInfoBuilder
     * @param TypeAnalyzer             $typeAnalyzer
     * @param string                   $file
     * @param string                   $code
     */
    public function __construct(
        NodeTypeDeducerInterface $nodeTypeDeducer,
        ClasslikeInfoBuilder $classlikeInfoBuilder,
        TypeAnalyzer $typeAnalyzer,
        string $file,
        string $code
    ) {
        $this->methodUsageFetchingVisitor = new MemberUsageFetchingVisitor(
            $nodeTypeDeducer,
            $classlikeInfoBuilder,
            $typeAnalyzer,
            $file,
            $code
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'unknownMembers';
    }

    /**
     * @inheritDoc
     */
    public function getVisitors(): array
    {
        return [
            $this->methodUsageFetchingVisitor
        ];
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        $output = [
            'expressionHasNoType'       => [],
            'expressionIsNotClasslike'  => [],
            'expressionHasNoSuchMember' => []
        ];

        $memberCallList = $this->methodUsageFetchingVisitor->getMemberCallList();

        foreach ($memberCallList as $memberCall) {
            $type = $memberCall['type'];

            unset ($memberCall['type']);

            if ($type === MemberUsageFetchingVisitor::TYPE_EXPRESSION_HAS_NO_TYPE) {
                $output['expressionHasNoType'][] = $memberCall;
            } elseif ($type === MemberUsageFetchingVisitor::TYPE_EXPRESSION_IS_NOT_CLASSLIKE) {
                $output['expressionIsNotClasslike'][] = $memberCall;
            } elseif ($type === MemberUsageFetchingVisitor::TYPE_EXPRESSION_HAS_NO_SUCH_MEMBER) {
                $output['expressionHasNoSuchMember'][] = $memberCall;
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getWarnings(): array
    {
        $output = [
            'expressionNewMemberWillBeCreated' => []
        ];

        $memberCallList = $this->methodUsageFetchingVisitor->getMemberCallList();

        foreach ($memberCallList as $memberCall) {
            $type = $memberCall['type'];

            unset ($memberCall['type']);

            if ($type === MemberUsageFetchingVisitor::TYPE_EXPRESSION_NEW_MEMBER_WILL_BE_CREATED) {
                $output['expressionNewMemberWillBeCreated'][] = $memberCall;
            }
        }

        return $output;
    }
}