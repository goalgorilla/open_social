<?php

namespace Drupal\social\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Disallows _private_module functions being called outside the private_module.
 *
 * Functions in a .module file don't have a visibility modifier that PHP can
 * enforce. It's common to start an internal function with an underscore (`_`).
 * Those private functions shouldn't be used outside of the module because
 * they're not part of the public API.
 *
 * @phpstan-implements Rule<FuncCall>
 */
class DisallowPrivateModuleFunctions implements Rule {

  /**
   * {@inheritdoc}
   */
  public function getNodeType(): string {
    return FuncCall::class;
  }

  /**
   * {@inheritdoc}
   */
  public function processNode(Node $node, Scope $scope): array {
    if (!$node->name instanceof Name) {
      return [];
    }

    $function = $node->name->toString();
    if (!str_starts_with($function, "_")) {
      return [];
    }

    $namespace = $scope->getNamespace();
    if ($namespace === NULL) {
      $module = $this->findModuleForFile($scope->getFile());
    }
    else {
      $matches = [];
      if (preg_match('/Drupal\\\\(?>Tests\\\\)?(\w+).*/', $namespace, $matches)) {
        $module = $matches[1];
      }
      else {
        $module = NULL;
      }
    }

    if ($module === NULL) {
      // If we're not in a module then we can't detect if this is our own
      // private function or not so we do nothing for now.
      return [];
    }

    // If the called function starts with the name of the current module then
    // we're allowing it. This might have a false positive on something like
    // calling `_social_profile_fields_secret()` from social_profile, but I
    // don't know how to fix that at the moment.
    if (str_starts_with($function, "_${module}_")) {
      return [];
    }

    return [
      RuleErrorBuilder::message(
        "Not allowed to call private function $function from module $module."
      )->build()
    ];
  }

  /**
   * Find the Drupal module that a certain file belongs to.
   *
   * @param string $file
   *   The file to determine the module for.
   * @return string|NULL
   *   The module name or NULL if the file wasn't in a Drupal module.
   */
  private function findModuleForFile(string $file) : ?string {
    $directory = dirname($file);
    do {
      $module_name = basename($directory);
      if (is_file($directory . DIRECTORY_SEPARATOR . "$module_name.info.yml")) {
        return $module_name;
      }
      $directory = dirname($directory);
    } while ($directory !== "/");

    return NULL;
  }

}
