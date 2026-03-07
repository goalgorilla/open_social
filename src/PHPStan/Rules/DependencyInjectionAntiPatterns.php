<?php

namespace Drupal\social\PHPStan\Rules;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Entity\EntityStorageInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Prevents common antipatterns for dependency injection.
 *
 * The antipatterns are documented by Matt Glaman in
 * https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal.
 *
 * @phpstan-implements Rule<Property>
 */
class DependencyInjectionAntiPatterns implements Rule {

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return Property::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    $classReflection = $scope->getClassReflection();
    if ($classReflection === NULL) {
      return [];
    }
    // If we're in a property that's declared in a trait we want to change some
    // output.
    $traitReflection = $scope->getTraitReflection();

    $errors = [];

    $storableConfigType = new ObjectType(StorableConfigBase::class);
    $entityStorageInterfaceType = new ObjectType(EntityStorageInterface::class);

    // A property statement can declare multiple props, we trigger for all of
    // them.
    foreach ($node->props as $prop) {
      $propertyName = $prop->name->toString();

      if ($node->isStatic()) {
        $propertyReflection = $classReflection->getStaticProperty($propertyName);
      }
      else {
        $propertyReflection = $classReflection->getInstanceProperty($propertyName, $scope);
      }

      $propertyType = $propertyReflection->getReadableType();
      $declaringClass = $propertyReflection->getDeclaringClass();

      // Skip inherited properties from parent classes.
      if ($traitReflection === NULL && $declaringClass->getName() !== $classReflection->getName()) {
        continue;
      }

      $inherited = "";
      if ($traitReflection !== NULL) {
        $inherited .= " (declared in trait {$traitReflection->getName()})";
      }

      if ($storableConfigType->isSuperTypeOf($propertyType)->yes()) {
        $errors[] = RuleErrorBuilder::message(
          "Config should not be stored as class property \$$propertyName$inherited, store a reference to the config factory and use ConfigFactoryInterface::get() for read-only access or getEditable() for mutable access at call-site.",
        )
          ->file($scope->getFile())
          ->line($node->getStartLine())
          ->identifier('diAntiPattern.configProperty')
          ->addTip("https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal")
          ->build();
      }

      if ($entityStorageInterfaceType->isSuperTypeOf($propertyType)->yes()) {
        $errors[] = RuleErrorBuilder::message(
          "Entity storage class should not be stored as class property \$$propertyName$inherited, store a reference to the entity type manager and use `EntityTypeManagerInterface::getStorage` at call-site.",
        )
          ->file($scope->getFile())
          ->line($node->getStartLine())
          ->identifier('diAntiPattern.entityStorageProperty')
          ->addTip("https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal")
          ->build();
      }

    }

    return $errors;
  }

}
