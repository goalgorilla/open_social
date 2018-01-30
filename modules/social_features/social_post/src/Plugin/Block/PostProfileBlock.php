<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a 'PostProfileBlock' block.
 *
 * @Block(
 *  id = "post_profile_block",
 *  admin_label = @Translation("Post on profile of others block"),
 * )
 */
class PostProfileBlock extends PostBlock {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entityTypeManager, $currentUser, $formBuilder, ModuleHandler $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $currentUser, $formBuilder, $moduleHandler);
    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'profile';
    $this->currentUser = $currentUser;

    // Check if current user is the same as the profile.
    // In this case use the default form display.
    $uid = $this->currentUser->id();
    $account_profile = \Drupal::routeMatch()->getParameter('user');
    if (isset($account_profile) && ($account_profile === $uid || (is_object($account_profile) && $uid === $account_profile->id()))) {
      $this->formDisplay = 'default';
    }

  }

}
