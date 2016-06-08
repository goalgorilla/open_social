<?php

/**
 * @file
 * Contains \Drupal\token\TokenEntityMapper.
 */

namespace Drupal\token;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Service to provide mappings between entity and token types.
 *
 * Why do we need this? Because when the token API was moved to core we did not
 * reuse the entity type as the base name for taxonomy terms and vocabulary
 * tokens.
 */
class TokenEntityMapper implements TokenEntityMapperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var array
   */
  protected $entityMappings;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeMappings() {
    if (empty($this->entityMappings)) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $info) {
        $this->entityMappings[$entity_type] = $info->get('token_type') ?: $entity_type;
      }
      // Allow modules to alter the mapping array.
      $this->moduleHandler->alter('token_entity_mapping', $this->entityMappings);
    }

    return $this->entityMappings;
  }

  /**
   * {@inheritdoc}
   */
  function getEntityTypeForTokenType($token_type, $fallback = FALSE) {
    if (empty($this->entityMappings)) {
      $this->getEntityTypeMappings();
    }

    $return = array_search($token_type, $this->entityMappings);
    return $return !== FALSE ? $return : ($fallback ? $token_type : FALSE);
  }

  /**
   * {@inheritdoc}
   */
  function getTokenTypeForEntityType($entity_type, $fallback = FALSE) {
    if (empty($this->entityMappings)) {
      $this->getEntityTypeMappings();
    }

    return isset($this->entityMappings[$entity_type]) ? $this->entityMappings[$entity_type] : ($fallback ? $entity_type : FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function resetInfo() {
    $this->entityMappings = NULL;
  }

}
