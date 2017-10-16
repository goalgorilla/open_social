<?php

namespace Drupal\social_post\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'PostGroupBlock' block.
 *
 * @Block(
 *  id = "post_group_block",
 *  admin_label = @Translation("Post on group block"),
 * )
 */
class PostGroupBlock extends PostBlock {

  public $entityType;
  public $bundle;
  public $formDisplay;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entityTypeManager, $currentUser, $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entityTypeManager, $currentUser, $formBuilder);
    $this->entityType = 'post';
    $this->bundle = 'post';
    $this->formDisplay = 'group';
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $group = _social_group_get_current_group();

    if (is_object($group)) {
      if ($group->hasPermission('add post entities in group', $account)) {
        $membership = $group->getMember($account);
        $context = [];
        if (!empty($membership)) {
          $group_content = $membership->getGroupContent();
          $context = ['group_content' => $group_content];
        }
        return \Drupal::entityTypeManager()
          ->getAccessControlHandler($this->entityType)
          ->createAccess($this->bundle, $account, $context, TRUE);
      }
    }

    // By default, the block is not visible.
    return AccessResult::forbidden()->setCacheMaxAge(0);
  }

}
