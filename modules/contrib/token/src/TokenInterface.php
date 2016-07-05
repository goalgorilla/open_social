<?php

/**
 * @file
 * Contains \Drupal\token\TokenInterface
 */

namespace Drupal\token;

interface TokenInterface {

  /**
   * Returns metadata describing supported token types.
   *
   * @param $token_type
   *   The token type for which the metadata is required.
   *
   * @return array[]
   *   An array of token type information from hook_token_info() for the
   *   specified token type.
   *
   * @see hook_token_info()
   * @see hook_token_info_alter()
   */
  public function getTypeInfo($token_type);

  /**
   * Returns metadata describing supported a token.
   *
   * @param $token_type
   *   The token type for which the metadata is required.
   * @param $token
   *   The token name for which the metadata is required.
   *
   * @return array[]
   *   An array of information from hook_token_info() for the specified token.
   *
   * @see hook_token_info()
   * @see hook_token_info_alter()
   *
   * @deprecated
   */
  public function getTokenInfo($token_type, $token);

  /**
   * Get a list of token types that can be used without any context (global).
   *
   * @return array[]
   *   An array of global token types.
   */
  public function getGlobalTokenTypes();

  /**
   * Validate an array of tokens based on their token type.
   *
   * @param string $type
   *   The type of tokens to validate (e.g. 'node', etc.)
   * @param string[] $tokens
   *   A keyed array of tokens, and their original raw form in the source text.
   *
   * @return string[]
   *   An array with the invalid tokens in their original raw forms.
   */
  function getInvalidTokens($type, $tokens);

  /**
   * Validate tokens in raw text based on possible contexts.
   *
   * @param string|string[] $value
   *   A string with the raw text containing the raw tokens, or an array of
   *   tokens from token_scan().
   * @param string[] $valid_types
   *   An array of token types that will be used when token replacement is
   *   performed.
   *
   * @return string[]
   *   An array with the invalid tokens in their original raw forms.
   */
  public function getInvalidTokensByContext($value, array $valid_types = []);
}
