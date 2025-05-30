<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Rector\Identical;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\FuncCall;
use Rector\NodeManipulator\BinaryOpManipulator;
use Rector\Php71\ValueObject\TwoNodeMatch;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\CodeQuality\Rector\Identical\SimplifyArraySearchRector\SimplifyArraySearchRectorTest
 */
final class SimplifyArraySearchRector extends AbstractRector
{
    public function __construct(
        private readonly BinaryOpManipulator $binaryOpManipulator,
        private readonly ValueResolver $valueResolver
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Simplify array_search to in_array',
            [
                new CodeSample('array_search("searching", $array) !== false;', 'in_array("searching", $array);'),
                new CodeSample(
                    'array_search("searching", $array, true) !== false;',
                    'in_array("searching", $array, true);'
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Identical::class, NotIdentical::class];
    }

    /**
     * @param Identical|NotIdentical $node
     */
    public function refactor(Node $node): ?Node
    {
        $twoNodeMatch = $this->binaryOpManipulator->matchFirstAndSecondConditionNode(
            $node,
            function (Node $node): bool {
                if (! $node instanceof FuncCall) {
                    return false;
                }

                return $this->isName($node, 'array_search');
            },
            fn (Node $node): bool => $node instanceof Expr && $this->valueResolver->isFalse($node)
        );

        if (! $twoNodeMatch instanceof TwoNodeMatch) {
            return null;
        }

        /** @var FuncCall $funcCallExpr */
        $funcCallExpr = $twoNodeMatch->getFirstExpr();

        $inArrayFuncCall = $this->nodeFactory->createFuncCall('in_array', $funcCallExpr->args);

        if ($node instanceof Identical) {
            return new BooleanNot($inArrayFuncCall);
        }

        return $inArrayFuncCall;
    }
}
