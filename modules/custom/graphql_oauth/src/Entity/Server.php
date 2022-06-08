<?php

namespace Drupal\graphql_oauth\Entity;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\graphql\Entity\Server as OriginalServer;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUserInterface;
use Drupal\simple_oauth\Oauth2ScopeInterface;
use Drupal\simple_oauth\Plugin\Field\FieldType\Oauth2ScopeReferenceItemListInterface;
use GraphQL\Error\UserError;
use GraphQL\Executor\Values;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;

/**
 * Overrides the GraphQL configuration and request entry point.
 */
class Server extends OriginalServer {

  /**
   * {@inheritdoc}
   */
  protected function getFieldResolver(ResolverRegistryInterface $registry) {
    $parent = parent::getFieldResolver($registry);
    if (is_callable($parent)) {
      $account_proxy = \Drupal::currentUser()->getAccount();
      if (!$account_proxy instanceof TokenAuthUserInterface) {
        // If the current user is not authenticated with OAuth then our
        // field resolver simply denies access to any protected field because
        // there is never a token, that has the scope.
        return function ($value, $args, ResolveContext $context, ResolveInfo $info) use ($parent) {
          $allow_directive_values = $this->getAllowDirectiveValues($info);
          if (!empty($allow_directive_values)) {
            throw new MissingDataException('Token with valid scope was required but no token was presented.');
          }
          return $parent($value, $args, $context, $info);
        };
      }
      $token = $account_proxy->getToken();
      $scopes_field = $token->get('scopes');
      assert($scopes_field instanceof Oauth2ScopeReferenceItemListInterface);
      $token_scopes = array_map(function (Oauth2ScopeInterface $scope) {
        return $scope->getName();
      }, $scopes_field->getScopes());
      // `auth_user_id` is only set for tokens created for specific users.
      // Application ("bot") tokens are not linked to specific users but their
      // user is determined by the user id configured for the consumer entity.
      $directive_name = !$token->get('auth_user_id')->isEmpty() ? 'allowUser' : 'allowBot';
      return function ($value, $args, ResolveContext $context, ResolveInfo $info) use ($parent, $directive_name, $token_scopes) {
        $allow_directive_values = $this->getAllowDirectiveValues($info);
        if (!empty($allow_directive_values)) {
          $this->checkAccess($allow_directive_values, $directive_name, $token_scopes);
        }
        return $parent($value, $args, $context, $info);
      };
    }
    return $parent;
  }

  /**
   * Get allow directive values.
   *
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   The resolve info object.
   *
   * @return array
   *   Allow directive values array.
   */
  private function getAllowDirectiveValues(ResolveInfo $info): array {
    $allow_directives = [
      'allowUser',
      'allowBot',
      'allowAll',
    ];
    $directive_values = [];

    foreach ($allow_directives as $allow_directive) {
      $directive_def = $info->schema->getDirective($allow_directive);

      if ($directive_def !== NULL) {
        $type = $info->returnType;
        // GraphQL wraps non-null and list definitions in `NonNull` and `List`
        // types but access directives are always on the concrete underlying
        // type, so we must unwrap our types to find the definitions.
        if ($type instanceof WrappingType) {
          $type = $type->getWrappedType(TRUE);
        }

        // Add directive values from the field.
        $this->addDirectiveValue($info->fieldDefinition->astNode, $info->fieldName, $directive_def, $directive_values);
        // Add directive values from the type.
        $this->addDirectiveValue($type->astNode, $type->name, $directive_def, $directive_values);
      }
    }

    return $directive_values;
  }

  /**
   * Add directive value.
   *
   * @param \GraphQL\Language\AST\TypeDefinitionNode|\GraphQL\Language\AST\FieldDefinitionNode|null $ast_node
   *   Abstract Syntax Tree definition.
   * @param string $def_name
   *   Name of the definition.
   * @param \GraphQL\Type\Definition\Directive $directive_def
   *   Directive definition.
   * @param array $directive_values
   *   Directive values.
   */
  private function addDirectiveValue($ast_node, string $def_name, Directive $directive_def, array &$directive_values): void {
    if ($ast_node !== NULL) {
      $values = Values::getDirectiveValues(
        $directive_def,
        $ast_node
      );
      if ($values !== NULL) {
        $directive_values[$directive_def->name][$def_name] = isset($directive_values[$directive_def->name][$def_name]) ? array_merge($directive_values[$directive_def->name][$def_name], $values) : $values;
      }
    }
  }

  /**
   * Checks access based on the directive values and token.
   *
   * @param array $directive_values
   *   Directive values.
   * @param string $directive_name
   *   The directive name.
   * @param array $token_scopes
   *   The granted scopes on the token.
   *
   * @phpstan-param "allowUser"|"allowBot" $directive_name
   *    The directive name.
   */
  private function checkAccess(array $directive_values, string $directive_name, array $token_scopes): void {
    $required_scopes = $this->getRequiredScopes($directive_name, $directive_values);

    // If there is no required scopes available for the associated directive,
    // this means the opposite directive is in effect.
    if (empty($required_scopes)) {
      $opposite_directive_name = $directive_name === 'allowUser' ? 'allowBot' : 'allowUser';
      $required_scopes = $this->getRequiredScopes($opposite_directive_name, $directive_values);
      $application_type = $directive_name === 'allowUser' ? 'User' : 'Bot';
      $def_name = key($required_scopes);
      throw new UserError(sprintf("Application type '%s' does not have access on '%s'.", $application_type, $def_name));
    }

    foreach ($required_scopes as $def_name => $value) {
      foreach ($value['requiredScopes'] as $required_scope) {
        if (!in_array($required_scope, $token_scopes)) {
          throw new UserError(sprintf("Missing scope '%s' on '%s'.", $required_scope, $def_name));
        }
      }
    }
  }

  /**
   * Retrieve the associated required scopes.
   *
   * @param string $directive_name
   *   The directive name to retrieve the required scopes from.
   * @param array $directive_values
   *   The directive values.
   *
   * @return array
   *   Returns the required scopes.
   */
  private function getRequiredScopes(string $directive_name, array $directive_values): array {
    $required_scopes = [];
    foreach ([$directive_name, 'allowAll'] as $allow_directive) {
      if (array_key_exists($allow_directive, $directive_values)) {
        $required_scopes = isset($required_scopes[$allow_directive]) ? array_merge($required_scopes[$allow_directive], $directive_values[$allow_directive]) : $directive_values[$allow_directive];
      }
    }

    return $required_scopes;
  }

}
