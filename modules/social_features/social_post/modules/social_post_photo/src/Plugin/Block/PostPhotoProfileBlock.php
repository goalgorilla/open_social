<?php

namespace Drupal\social_post_photo\Plugin\Block;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_group\CurrentGroupService;
use Drupal\social_post\Plugin\Block\PostProfileBlock;

/**
 * Provides a 'PostPhotoProfileBlock' block.
 *
 * @Block(
 *   id = "post_photo_profile_block",
 *   admin_label = @Translation("Post photo on profile of others block"),
 * )
 */
class PostPhotoProfileBlock extends PostProfileBlock {

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

    // Override the bundle type.
    $this->bundle = 'photo';
  }

}
