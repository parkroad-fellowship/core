<?php

namespace PRF\PHPStan\Rules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Jobs, listeners and actions must write through models so observers, domain events and the
 * activity log run. Query-level writes on an Eloquent Builder or Relation skip all of them.
 *
 * @implements Rule<MethodCall>
 */
final class NoQueryBuilderWritesRule implements Rule
{
    private const WRITE_METHODS = ['update', 'delete', 'forcedelete', 'restore', 'increment', 'decrement', 'upsert'];

    private const GUARDED_NAMESPACES = ['App\\Jobs\\', 'App\\Listeners\\', 'App\\Actions\\'];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || !in_array($node->name->toLowerString(), self::WRITE_METHODS, true)) {
            return [];
        }

        $class = $scope->getClassReflection()?->getName() ?? '';

        if (!$this->isGuarded($class)) {
            return [];
        }

        $callerType = $scope->getType($node->var);

        $isQueryLevel = new ObjectType(Builder::class)->isSuperTypeOf($callerType)->yes()
            || new ObjectType(Relation::class)->isSuperTypeOf($callerType)->yes();

        if (!$isQueryLevel) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Query-level %s() skips observers, domain events and the activity log. Load the model(s) and call %s() on each (see .ai/guidelines/prf/jobs-and-side-effects.md).',
                $node->name->toString(),
                $node->name->toString(),
            ))
                ->identifier('prf.noQueryBuilderWrites')
                ->build(),
        ];
    }

    private function isGuarded(string $class): bool
    {
        foreach (self::GUARDED_NAMESPACES as $namespace) {
            if (str_starts_with($class, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
