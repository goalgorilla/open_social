<?php

namespace Drupal\social\PHPStan\Rules;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Entity\EntityStorageInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Prevents DI antipatterns for constructor-promoted parameters.
 *
 * Applies the same checks as DependencyInjectionAntiPatterns but for
 * promoted constructor parameters (EntityStorageInterface, StorableConfigBase).
 * Processes __construct methods and iterates their parameters so the rule runs
 * once per constructor instead of once per parameter, and does not rely on
 * the parent node attribute.
 *
 * @phpstan-implements Rule<ClassMethod>
 */
class DependencyInjectionAntiPatternsParam implements Rule {

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return ClassMethod::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    if ($node->name->toString() !== '__construct') {
      return [];
    }

    $paramReflections = $scope->getFunction()?->getParameters();
    if ($paramReflections === NULL) {
      return [];
    }

    $storableConfigType = new ObjectType(StorableConfigBase::class);
    $entityStorageInterfaceType = new ObjectType(EntityStorageInterface::class);
    $errors = [];

    foreach ($node->params as $index => $param) {
      if (!$param->isPromoted()) {
        continue;
      }

      $paramReflection = $paramReflections[$index] ?? NULL;
      if ($paramReflection === NULL) {
        continue;
      }

      $paramType = $paramReflection->getType();
      $paramName = $param->var instanceof Variable && is_string($param->var->name)
        ? $param->var->name
        : '';

      if ($storableConfigType->isSuperTypeOf($paramType)->yes()) {
        $errors[] = RuleErrorBuilder::message(
          "Config should not be stored as promoted constructor parameter \$$paramName, store a reference to the config factory and use ConfigFactoryInterface::get() for read-only access or getEditable() for mutable access at call-site.",
        )
          ->file($scope->getFile())
          ->line($param->getStartLine())
          ->identifier('diAntiPattern.configProperty')
          ->addTip("https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal")
          ->build();
      }

      if ($entityStorageInterfaceType->isSuperTypeOf($paramType)->yes()) {
        $errors[] = RuleErrorBuilder::message(
          "Entity storage class should not be stored as promoted constructor parameter \$$paramName, store a reference to the entity type manager and use `EntityTypeManagerInterface::getStorage` at call-site.",
        )
          ->file($scope->getFile())
          ->line($param->getStartLine())
          ->identifier('diAntiPattern.entityStorageProperty')
          ->addTip("https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal")
          ->build();
      }
    }

    return $errors;
  }

}
