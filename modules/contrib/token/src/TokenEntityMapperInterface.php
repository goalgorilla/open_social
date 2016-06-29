<?php

/**
 * @file
 * Contains \Drupal\token\TokenEntityMapperInterface.
 */

namespace Drupal\token;

interface TokenEntityMapperInterface {

  /**
   * Return an array of entity type to token type mappings.
   *
   * @return array
   *   An array of mappings with entity type mapping to token type.
   */
  public function getEntityTypeMappings();

  /**
   * Return the entity type of a particular token type.
   *
   * @param string $token_type
   *   The token type for which the mapping is returned.
   * @param bool $fallback
   *   (optional) Defaults to FALSE. If true, the same $value is returned in
   *   case the mapping was not found.
   *
   * @return string
   *   The entity type of the token type specified.
   *
   * @see token_entity_info_alter()
   * @see http://drupal.org/node/737726
   */
  function getEntityTypeForTokenType($token_type, $fallback = FALSE);

  /**
   * Return the token type of a particular entity type.
   *
   * @param string $entity_type
   *   The entity type for which the mapping is returned.
   * @param bool $fallback
   *   (optional) Defaults to FALSE. If true, the same $value is returned in
   *   case the mapping was not found.
   *
   * @return string
   *   The token type of the entity type specified.
   *
   * @see token_entity_info_alter()
   * @see http://drupal.org/node/737726
   */
  function getTokenTypeForEntityType($entity_type, $fallback = FALSE);

  /**
   * Resets metadata describing token and entity mappings.
   */
  public function resetInfo();
}
