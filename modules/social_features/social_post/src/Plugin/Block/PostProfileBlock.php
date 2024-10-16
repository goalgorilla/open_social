<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_group\CurrentGroupService;

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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    FormBuilderInterface $form_builder,
    ModuleHandlerInterface $module_handler,
    CurrentRouteMatch $route_match,
    CurrentGroupService $current_group_service,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $current_user,
      $form_builder,
      $module_handler,
      $route_match,
      $current_group_service,
    );

    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'profile';

    // Check if current user is the same as the profile.
    // In this case use the default form display.
    $account = $this->routeMatch->getParameter('user');
    $uid = $this->currentUser->id();
    if (isset($account) && ($account === $uid || (is_object($account) && $uid === $account->id()))) {
      $this->formDisplay = 'default';
    }
  }

}
