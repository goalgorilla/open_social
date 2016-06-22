<?php

/**
 * @file
 * Contains \Drupal\group\Controller\GroupMembershipController.
 */

namespace Drupal\group\Controller;

use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group membership route controllers.
 *
 * This only controls the routes that are not supported out of the box by the
 * plugin base \Drupal\group\Plugin\GroupContentEnablerBase.
 */
class GroupMembershipController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupMembershipController.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Provides the form for joining a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return array
   *   A group join form.
   */
  public function join(GroupInterface $group) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');

    // Pre-populate a group membership with the current user.
    $group_content = GroupContent::create([
      'type' => $plugin->getContentTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $this->currentUser->id(),
    ]);

    return $this->entityFormBuilder()->getForm($group_content, 'group-join');
  }

  /**
   * The _title_callback for the join form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to join.
   *
   * @return string
   *   The page title.
   */
  public function joinTitle(GroupInterface $group) {
    return $this->t('Join group %label', ['%label' => $group->label()]);
  }

  /**
   * Provides the form for leaving a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to leave.
   *
   * @return array
   *   A group leave form.
   */
  public function leave(GroupInterface $group) {
    $group_content = $group->getMember($this->currentUser)->getGroupContent();
    return $this->entityFormBuilder()->getForm($group_content, 'group-leave');
  }

}
