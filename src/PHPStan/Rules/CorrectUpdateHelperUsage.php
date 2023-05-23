<?php

namespace Drupal\social\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Prevents using UpdateHelper's `executeUpdate` function incorrectly.
 *
 * The function should only be used within hook_update_N functions since install
 * conditions may change which requires changing what happens in an install
 * hook but the upgrade path should remain static.
 *
 * @phpstan-implements Rule<MethodCall>
 */
class CorrectUpdateHelperUsage implements Rule {

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return MethodCall::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    if (!$node->name instanceof Node\Identifier || (string) $node->name !== "executeUpdate") {
      return [];
    }
    $module = basename(dirname($scope->getFile()));
    $function = (string) $scope->getFunctionName();

    if (preg_match("/${module}_update_\d+/", $function) === 1) {
      return [];
    }

    return [
      RuleErrorBuilder::message(
        "Called UpdateHelper::executeUpdate in $function but this should only be called within a hook_update_N function."
      )->build()
    ];
  }

}
