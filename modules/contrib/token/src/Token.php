<?php

/**
 * @file
 * Contains \Drupal\token\Token.
 */

namespace Drupal\token;

use Drupal\Core\Utility\Token as TokenBase;

/**
 * Service to retrieve token information.
 *
 * This service replaces the core's token service and provides the same
 * functionality by extending it. It also provides additional functionality
 * commonly required by the additional support provided by token module and
 * other modules.
 */
class Token extends TokenBase implements TokenInterface {

  /**
   * Token definitions.
   *
   * @var array[]|null
   *   An array of token definitions, or NULL when the definitions are not set.
   *
   * @see self::resetInfo()
   */
  protected $globalTokenTypes;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    if (empty($this->tokenInfo)) {
      $token_info = parent::getInfo();

      foreach (array_keys($token_info['types']) as $type_key) {
        if (isset($token_info['types'][$type_key]['type'])) {
          $base_type = $token_info['types'][$type_key]['type'];
          // If this token type extends another token type, then merge in
          // the base token type's tokens.
          if (isset($token_info['tokens'][$base_type])) {
            $token_info['tokens'] += [$type_key => []];
            $token_info['tokens'][$type_key] += $token_info['tokens'][$base_type];
          }
        }
        else {
          // Add a 'type' value to each token type information.
          $token_info['types'][$type_key]['type'] = $type_key;
        }
      }

      // Pre-sort tokens.
      uasort($token_info['types'], [$this, 'sortTokens']);
      foreach (array_keys($token_info['tokens']) as $type) {
        uasort($token_info['tokens'][$type], [$this, 'sortTokens']);
      }

      $this->tokenInfo = $token_info;
    }

    return $this->tokenInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenInfo($token_type, $token) {
    if (empty($this->tokenInfo)) {
      $this->getInfo();
    }

    return isset($this->tokenInfo['tokens'][$token_type][$token]) ? $this->tokenInfo['tokens'][$token_type][$token] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeInfo($token_type) {
    if (empty($this->tokenInfo)) {
      $this->getInfo();
    }

    return isset($this->tokenInfo['types'][$token_type]) ? $this->tokenInfo['types'][$token_type] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalTokenTypes() {
    if (empty($this->globalTokenTypes)) {
      $token_info = $this->getInfo();
      foreach ($token_info['types'] as $type => $type_info) {
        // If the token types has not specified that 'needs-data' => TRUE, then
        // it is a global token type that will always be replaced in any context.
        if (empty($type_info['needs-data'])) {
          $this->globalTokenTypes[] = $type;
        }
      }
    }

    return $this->globalTokenTypes;
  }

  /**
   * {@inheritdoc}
   */
  function getInvalidTokens($type, $tokens) {
    $token_info = $this->getInfo();
    $invalid_tokens = array();

    foreach ($tokens as $token => $full_token) {
      if (isset($token_info['tokens'][$type][$token])) {
        continue;
      }

      // Split token up if it has chains.
      $parts = explode(':', $token, 2);

      if (!isset($token_info['tokens'][$type][$parts[0]])) {
        // This is an invalid token (not defined).
        $invalid_tokens[] = $full_token;
      }
      elseif (count($parts) == 2) {
        $sub_token_info = $token_info['tokens'][$type][$parts[0]];
        if (!empty($sub_token_info['dynamic'])) {
          // If this token has been flagged as a dynamic token, skip it.
          continue;
        }
        elseif (empty($sub_token_info['type'])) {
          // If the token has chains, but does not support it, it is invalid.
          $invalid_tokens[] = $full_token;
        }
        else {
          // Recursively check the chained tokens.
          $sub_tokens = $this->findWithPrefix(array($token => $full_token), $parts[0]);
          $invalid_tokens = array_merge($invalid_tokens, $this->getInvalidTokens($sub_token_info['type'], $sub_tokens));
        }
      }
    }

    return $invalid_tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function getInvalidTokensByContext($value, array $valid_types = []) {
    if (in_array('all', $valid_types)) {
      $info = $this->getInfo();
      $valid_types = array_keys($info['types']);
    }
    else {
      // Add the token types that are always valid in global context.
      $valid_types = array_merge($valid_types, $this->getGlobalTokenTypes());
    }

    $invalid_tokens = array();
    $value_tokens = is_string($value) ? $this->scan($value) : $value;

    foreach ($value_tokens as $type => $tokens) {
      if (!in_array($type, $valid_types)) {
        // If the token type is not a valid context, its tokens are invalid.
        $invalid_tokens = array_merge($invalid_tokens, array_values($tokens));
      }
      else {
        // Check each individual token for validity.
        $invalid_tokens = array_merge($invalid_tokens, $this->getInvalidTokens($type, $tokens));
      }
    }

    array_unique($invalid_tokens);
    return $invalid_tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function resetInfo() {
    parent::resetInfo();
    $this->globalTokenTypes = NULL;
  }

  /**
   * uasort() callback to sort tokens by the 'name' property.
   */
  protected function sortTokens($token_a, $token_b) {
    return strnatcmp($token_a['name'], $token_b['name']);
  }
}
