<?php

namespace Drupal\social_post\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides dynamic permissions for posts of different types.
 */
class PostPermissions implements PostPermissionsInterface {

  use StringTranslationTrait;

  /**
   * The post type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * PostPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $translation
  ) {
    $this->storage = $entity_type_manager->getStorage('post_type');
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    $permissions = [];

    /** @var \Drupal\social_post\Entity\PostTypeInterface $type */
    foreach ($this->storage->loadMultiple() as $type_id => $type) {
      $permissions["add $type_id post entities"] = [
        'title' => $this->t('%type_name: Create new Post entities', [
          '%type_name' => $type->label(),
        ]),
      ];
    }

    return $permissions;
  }

}
