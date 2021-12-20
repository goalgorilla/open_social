<?php

namespace Drupal\social_group;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for join plugins.
 */
interface JoinPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * JoinBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    TranslationInterface $translation
  );

  /**
   * Gets a list of clickable elements.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity object.
   * @param \Drupal\user\UserInterface $account
   *   The user entity object.
   */
  public function actions(ContentEntityInterface $entity, UserInterface $account): array;

}
