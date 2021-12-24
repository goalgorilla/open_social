<?php

namespace Drupal\social_group;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    TranslationInterface $translation,
    AccountProxyInterface $current_user
  );

  /**
   * Gets a list of clickable elements.
   *
   * @param \Drupal\social_group\EntityMemberInterface $entity
   *   The membership entity object.
   * @param array $variables
   *   The variables.
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array;

}
