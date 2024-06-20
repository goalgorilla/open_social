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
   * Get subfield labels.
   *
   * @return array<string, TranslatableMarkup>
   *   Returns an array of subfields, where key is subfield machine name and
   *     value is label.
   */
  public function getSubfieldLabels(): array {
    return [
      'external_id' => new TranslatableMarkup('External ID'),
      'external_owner_target_type' => new TranslatableMarkup('Target Entity Type'),
      'external_owner_id' => new TranslatableMarkup('External Owner'),
    ];
  }

  /**
   * Get subfield label.
   *
   * @param string $subfield_machine_name
   *   Subfield machine name.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns subfield label string.
   */
  public function getSubfieldLabel(string $subfield_machine_name): TranslatableMarkup {
    assert(isset($this->getSubfieldLabels()[$subfield_machine_name]), 'The subfield ' . $subfield_machine_name . ' does not exist.');
    return $this->getSubfieldLabels()[$subfield_machine_name];
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
