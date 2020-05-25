<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PostProfileBlock' block.
 *
 * @Block(
 *   id = "post_profile_block",
 *   admin_label = @Translation("Post on profile of others block"),
 * )
 */
class PostProfileBlock extends PostBlock {

  /**
   * PostProfileBlock constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param mixed $account
   *   The user object or user ID.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    FormBuilderInterface $form_builder,
    ModuleHandlerInterface $module_handler,
    $account
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user,
      $form_builder,
      $module_handler
    );

    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'profile';

    // Check if current user is the same as the profile.
    // In this case use the default form display.
    $uid = $this->currentUser->id();
    if (isset($account) && ($account === $uid || (is_object($account) && $uid === $account->id()))) {
      $this->formDisplay = 'default';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('module_handler'),
      $container->get('current_route_match')->getParameter('user')
    );
  }

}
