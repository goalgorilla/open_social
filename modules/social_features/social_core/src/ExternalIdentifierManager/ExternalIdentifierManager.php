<?php

namespace Drupal\social_core\ExternalIdentifierManager;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Service for External Identifier field type.
 */
class ExternalIdentifierManager {

  use StringTranslationTrait;

  /**
   * Constructs a ExternalIdentifierManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler
  ) {
  }

  /**
   * Returns list of allowed External Owner Entity Types.
   *
   * @return array<string, TranslatableMarkup>
   *   Returns an array of allowed target types, where key is target type
   *   machine name and value is label.
   */
  public function getAllowedExternalOwnerTargetTypes(): array {
    $allowed_external_owner_target_types = [];

    if ($this->moduleHandler->moduleExists('consumers')) {
      $allowed_external_owner_target_types['consumer'] = $this->t('Consumer');
    }

    return $allowed_external_owner_target_types;
  }

  /**
   * Get valid target types as comma separated string.
   *
   * @return string
   *   Returns valid target types as comma separated string.
   */
  public function getValidTargetTypesAsString(): string {
    return implode(', ', array_keys($this->getAllowedExternalOwnerTargetTypes()));
  }

}
