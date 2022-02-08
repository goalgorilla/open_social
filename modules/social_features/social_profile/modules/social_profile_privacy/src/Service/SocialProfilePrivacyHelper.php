<?php

namespace Drupal\social_profile_privacy\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the helper service.
 *
 * @package Drupal\social_profile_privacy\Service
 */
class SocialProfilePrivacyHelper implements SocialProfilePrivacyHelperInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The module handler.
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * SocialProfilePrivacyHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldOptions(AccountInterface $account = NULL) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $this->entityTypeManager->getStorage('entity_form_display')
      ->load('profile.profile.default');

    $display_context = [
      'entity_type' => 'profile',
      'bundle' => 'profile',
      'form_mode' => 'default',
    ];

    $this->moduleHandler->alter('entity_form_display', $display, $display_context);

    $definitions = $this->entityFieldManager->getFieldDefinitions('profile', 'profile');
    $handler = $this->entityTypeManager->getAccessControlHandler('profile');

    if ($account) {
      /** @var \Drupal\profile\ProfileStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('profile');

      $profile = $storage->loadByUser($account, 'profile');
    }

    $options = [];

    /** @var string $field */
    foreach (array_keys($display->getComponents()) as $field) {
      $definition = $definitions[$field];

      if ($definition instanceof FieldConfigInterface) {
        $items = isset($profile) ? $profile->get($field) : NULL;

        $options[$field] = [
          'label' => $definition->label(),
          'access' => $handler->fieldAccess('edit', $definition, $account, $items),
        ];
      }
    }

    return $options;
  }

}
