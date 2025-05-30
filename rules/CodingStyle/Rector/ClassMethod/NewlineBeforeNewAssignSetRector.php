<?php

declare(strict_types=1);

namespace Rector\CodingStyle\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Nop;
use Rector\Contract\Rector\HTMLAverseRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Tests\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector\NewlineBeforeNewAssignSetRectorTest
 */
final class NewlineBeforeNewAssignSetRector extends AbstractRector implements HTMLAverseRectorInterface
{
    private ?string $previousStmtVariableName = null;

    private ?string $previousPreviousStmtVariableName = null;

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add extra space before new assign set', [
            new CodeSample(
                <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $value = new Value;
        $value->setValue(5);
        $value2 = new Value;
        $value2->setValue(1);
        $foo = new Value;
        $foo->bar = 5;
        $bar = new Value;
        $bar->foo = 1;
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        $value = new Value;
        $value->setValue(5);

        $value2 = new Value;
        $value2->setValue(1);

        $foo = new Value;
        $foo->bar = 5;

        $bar = new Value;
        $bar->foo = 1;
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class, Function_::class, Closure::class];
    }

    /**
     * @param ClassMethod|Function_|Closure $node
     */
    public function refactor(Node $node): ?Node
    {
        // skip methods with no bodies (e.g interface methods)
        if ($node->stmts === null) {
            return null;
        }

        $this->reset();

        $hasChanged = false;
        $newStmts = [];

        foreach ($node->stmts as $key => $stmt) {
            $currentStmtVariableName = $this->resolveCurrentStmtVariableName($stmt);

            if ($this->shouldAddEmptyLine($currentStmtVariableName, $node, $key)) {
                $hasChanged = true;
                // insert newline before stmt
                $newStmts[] = new Nop();
            }

            $newStmts[] = $stmt;

            $this->previousPreviousStmtVariableName = $this->previousStmtVariableName;
            $this->previousStmtVariableName = $currentStmtVariableName;
        }

        $node->stmts = $newStmts;

        return $hasChanged ? $node : null;
    }

    private function reset(): void
    {
        $this->previousStmtVariableName = null;
        $this->previousPreviousStmtVariableName = null;
    }

    private function resolveCurrentStmtVariableName(Stmt $stmt): ?string
    {
        if (! $stmt instanceof Expression) {
            return null;
        }

        $stmtExpr = $stmt->expr;

        if ($stmtExpr instanceof Assign || $stmtExpr instanceof MethodCall) {
            if ($this->shouldSkipLeftVariable($stmtExpr)) {
                return null;
            }

            if (! $stmtExpr->var instanceof MethodCall && ! $stmtExpr->var instanceof StaticCall) {
                $nodeVar = $stmtExpr->var;

                if ($nodeVar instanceof PropertyFetch) {
                    do {
                        $previous = $nodeVar;
                        $nodeVar = $nodeVar->var;
                    } while ($nodeVar instanceof PropertyFetch);

                    if ($this->getName($nodeVar) === 'this') {
                        $nodeVar = $previous;
                    }
                }

                return $this->getName($nodeVar);
            }
        }

        return null;
    }

    private function shouldAddEmptyLine(
        ?string $currentStmtVariableName,
        ClassMethod | Function_ | Closure $node,
        int $key
    ): bool {
        if (! $this->isNewVariableThanBefore($currentStmtVariableName)) {
            return false;
        }

        // this is already empty line before
        return ! $this->isPrecededByEmptyLine($node, $key);
    }

    private function shouldSkipLeftVariable(Assign | MethodCall $node): bool
    {
        if (! $node->var instanceof Variable) {
            return false;
        }

        // local method call
        return $this->isName($node->var, 'this');
    }

    private function isNewVariableThanBefore(?string $currentStmtVariableName): bool
    {
        if ($this->previousPreviousStmtVariableName === null) {
            return false;
        }

        if ($this->previousStmtVariableName === null) {
            return false;
        }

        if ($currentStmtVariableName === null) {
            return false;
        }

        if ($this->previousStmtVariableName !== $this->previousPreviousStmtVariableName) {
            return false;
        }

        return $this->previousStmtVariableName !== $currentStmtVariableName;
    }

    private function isPrecededByEmptyLine(ClassMethod | Function_ | Closure $node, int $key): bool
    {
        if ($node->stmts === null) {
            return false;
        }

        $previousNode = $node->stmts[$key - 1];
        $currentNode = $node->stmts[$key];

        return abs($currentNode->getStartLine() - $previousNode->getStartLine()) >= 2;
    }
}
