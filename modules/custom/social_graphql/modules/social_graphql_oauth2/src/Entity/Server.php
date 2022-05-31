<?php

namespace Drupal\social_graphql_oauth2\Entity;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\graphql\Entity\Server as OriginalServer;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUserInterface;
use Drupal\simple_oauth\Entity\Oauth2TokenInterface;
use Drupal\simple_oauth\Oauth2ScopeInterface;
use GraphQL\Error\UserError;
use GraphQL\Executor\Values;
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
      $token = NULL;
      if ($account_proxy instanceof TokenAuthUserInterface) {
        $token = $account_proxy->getToken();
      }

      return function ($value, $args, ResolveContext $context, ResolveInfo $info) use ($parent, $token) {
        $allow_directive_values = $this->getAllowDirectiveValues($info);
        if ($allow_directive_values) {
          // If token is not available; it has been removed or a different
          // authentication is used. In this case we restrict access on
          // types/fields that have the 'allow' directive.
          if ($token === NULL) {
            throw new MissingDataException('There is no OAuth2 token to work on.');
          }
          $this->checkAccess($allow_directive_values, $token);
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
        if ($type instanceof WrappingType) {
          $type = $type->getWrappedType(TRUE);
        }

        // Get directive values on the type.
        $type_directive_values = $type->astNode !== NULL ? Values::getDirectiveValues(
          $directive_def,
          $type->astNode
        ) : NULL;

        // Get directive values on the field.
        $field_directive_values = $info->fieldDefinition->astNode ? Values::getDirectiveValues(
          $directive_def,
          $info->fieldDefinition->astNode
        ) : NULL;

        $values = $type_directive_values ?: $field_directive_values;

        if (!empty($values)) {
          $directive_values[$allow_directive] = $values;
        }
      }
    }

    return $directive_values;
  }

  /**
   * Checks access based on the directive values and token.
   *
   * @param array $directive_values
   *   The directive values.
   * @param \Drupal\simple_oauth\Entity\Oauth2TokenInterface $token
   *   The OAuth2 token.
   */
  private function checkAccess(array $directive_values, Oauth2TokenInterface $token): void {
    $token_has_user = !$token->get('auth_user_id')->isEmpty();
    /** @var \Drupal\simple_oauth\Plugin\Field\FieldType\Oauth2ScopeReferenceItemListInterface $scopes_field */
    $scopes_field = $token->get('scopes');
    $token_scopes = array_map(function (Oauth2ScopeInterface $scope) {
      return $scope->getName();
    }, $scopes_field->getScopes());

    $directive_name = $token_has_user ? 'allowUser' : 'allowBot';
    $grant_type = $token_has_user ? 'client credentials' : 'authorization code';
    $required_scopes = $this->getRequiredScopes($directive_name, $directive_values);
    if (empty($required_scopes)) {
      throw new UserError(sprintf("The '%s' grant type is required.", $grant_type));
    }
    $this->checkRequiredScopes($required_scopes, $token_scopes);
  }

  /**
   * Checks if the access token has the scope id's.
   *
   * @param array $required_scopes
   *   The required scope id's.
   * @param array $token_scopes
   *   The scopes on the token.
   *
   * @throws \GraphQL\Error\UserError
   */
  private function checkRequiredScopes(array $required_scopes, array $token_scopes): void {
    foreach ($required_scopes as $scope) {
      if (!in_array($scope, $token_scopes)) {
        throw new UserError(sprintf("The '%s' scope is required.", $scope));
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

    if (isset($directive_values[$directive_name]['requiredScopes'])) {
      $required_scopes = $directive_values[$directive_name]['requiredScopes'];
    }
    if (isset($directive_values['allowAll']['requiredScopes'])) {
      $required_scopes = empty($required_scopes) ? $directive_values['allowAll']['requiredScopes'] : array_merge($directive_values[$directive_name]['requiredScopes'], $required_scopes);
    }

    return $required_scopes;
  }

}
