<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Group routes.
 */
class SocialGroupController extends ControllerBase {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SocialGroupController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * The _title_callback for the view.group_members.page_group_members route.
   *
   * @param object $group
   *   The group ID.
   *
   * @return string
   *   The page title.
   */
  public function groupMembersTitle($group) {
    // If it's not a group then it's a gid.
    if (!$group instanceof Group) {
      $group = Group::load($group);
    }
    return $this->t('Members of @name', ['@name' => $group->label()]);
  }

  /**
   * The _title_callback for the view.posts.block_stream_group route.
   *
   * @param object $group
   *   The group ID.
   *
   * @return string
   *   The page title.
   */
  public function groupStreamTitle($group) {
    $group_label = $group->label();
    return $group_label;
  }

  /**
   * Callback function of the stream page of a group.
   *
   * @return array
   *   A renderable array.
   */
  public function groupStream() {
    return [
      '#markup' => '',
    ];
  }

  /**
   * The title callback for the entity.group_content.add_form.
   *
   * @return string
   *   The page title.
   */
  public function groupAddMemberTitle() {
    return $this->t('Add members');
  }

  /**
   * The title callback for the entity.group_content.delete-form.
   *
   * @return string
   *   The page title.
   */
  public function groupRemoveContentTitle() {
    return $this->t('Remove a member');
  }

  /**
   * Function that checks access on the my topic pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account we need to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If access is allowed.
   */
  public function myGroupAccess(AccountInterface $account) {
    // Fetch user from url.
    $user = $this->requestStack->getCurrentRequest()->get('user');
    // If we don't have a user in the request, assume it's my own profile.
    if (is_null($user)) {
      // Usecase is the user menu, which is generated on all LU pages.
      $user = User::load($account->id());
    }

    // If not a user then just return neutral.
    if (!$user instanceof User) {
      $user = User::load($user);

      if (!$user instanceof User) {
        return AccessResult::neutral();
      }
    }

    // Own profile?
    if ($user->id() === $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'view groups on my profile');
    }
    return AccessResult::allowedIfHasPermission($account, 'view groups on other profiles');
  }

}
