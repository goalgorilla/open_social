<?php

declare(strict_types=1);

namespace Drupal\social_core\Service;

use Drupal\field\FieldUninstallValidator;

/**
 * Extends the field uninstall validator to allow enforced dependency cleanup.
 *
 * Drupal core's FieldUninstallValidator blocks module uninstall if the module
 * provides a field type that is in use. However, when all field storages using
 * that field type have an enforced dependency on the module, they will be
 * automatically deleted during the uninstall process. This class suppresses
 * the validation error in that case.
 *
 * Registered via SocialCoreServiceProvider::alter() which swaps the class
 * on the original field.uninstall_validator service definition. This preserves
 * the service_collector tag so the ModuleInstaller picks up this validator.
 */
class FieldUninstallValidatorOverride extends FieldUninstallValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = parent::validate($module);
    if (empty($reasons)) {
      return [];
    }

    // Check if all field storages provided by this module have an enforced
    // dependency on the module. If so, they will be automatically deleted
    // during uninstall, so the validation error can be suppressed.
    $field_storages = $this->fieldStorageConfigStorage
      ->loadByProperties(['module' => $module]);

    if (empty($field_storages)) {
      return $reasons;
    }

    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    foreach ($field_storages as $field_storage) {
      // Use get() to access the raw dependencies array, because
      // getDependencies() merges enforced deps into regular deps and
      // removes the 'enforced' key.
      $dependencies = $field_storage->get('dependencies') ?? [];
      $enforced_modules = $dependencies['enforced']['module'] ?? [];
      if (!in_array($module, $enforced_modules)) {
        // At least one field storage does not have an enforced dependency,
        // so it won't be cleaned up automatically. Keep the original reasons.
        return $reasons;
      }
    }

    // All field storages have enforced dependencies on this module and will be
    // deleted during uninstall. Allow the uninstall to proceed.
    return [];
  }

}
