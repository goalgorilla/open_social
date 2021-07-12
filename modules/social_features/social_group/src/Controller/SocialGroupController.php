<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\Entity\User;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Group routes.
 */
class SocialGroupController extends ControllerBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Views Bulk Operations action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface
   */
  protected $actionProcessor;


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
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   Private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(RequestStack $requestStack, PrivateTempStoreFactory $tempStoreFactory, ViewsBulkOperationsActionProcessorInterface $actionProcessor) {
    $this->requestStack = $requestStack;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionProcessor = $actionProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor')
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
    $group_content = \Drupal::routeMatch()->getParameter('group_content');
    $group = \Drupal::routeMatch()->getParameter('group');
    if ($group_content instanceof GroupContent &&
      $group_content->getGroupContentType()->getContentPluginId() === 'group_invitation') {
      if ($group instanceof GroupInterface) {
        return $this->t('Add invites to group: @group_name', ['@group_name' => $group->label()]);
      }
      return $this->t('Add invites');
    }
    if ($group instanceof GroupInterface) {
      return $this->t('Add members to group: @group_name', ['@group_name' => $group->label()]);
    }

    return $this->t('Add members');
  }

  /**
   * The title callback for the entity.group_content.delete-form.
   *
   * @return string
   *   The page title.
   */
  public function groupRemoveContentTitle($group) {
    $group_content = \Drupal::routeMatch()->getParameter('group_content');
    if ($group_content instanceof GroupContent &&
      $group_content->getGroupContentType()->getContentPluginId() === 'group_invitation') {
      $group = \Drupal::routeMatch()->getParameter('group');
      if ($group instanceof GroupInterface) {
        return $this->t('Remove invitee from group: @group_name', ['@group_name' => $group->label()]);
      }
      return $this->t('Remove invitation');
    }
    return $this->t('Remove a member');
  }

  /**
   * Function that checks access on the my groups pages.
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

    if ($user->isBlocked()) {
      return AccessResult::allowedIfHasPermission($account, 'view blocked user');
    }

    // Own profile?
    if ($user->id() === $account->id()) {
      return AccessResult::allowedIfHasPermission($account, 'view groups on my profile');
    }
    return AccessResult::allowedIfHasPermission($account, 'view groups on other profiles');
  }

  /**
   * Redirects users to their groups page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the groups of the currently logged in user.
   */
  public function redirectMyGroups() {
    return $this->redirect('view.groups.page_user_groups', [
      'user' => $this->currentUser()->id(),
    ]);
  }

  /**
   * OtherGroupPage.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return Redirect to the group account.
   */
  public function otherGroupPage($group) {
    return $this->redirect('entity.group.canonical', ['group' => $group]);
  }

  /**
   * The _title_callback for the entity.group_content.create_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create the group content in.
   * @param string $plugin_id
   *   The group content enabler to create content with.
   *
   * @return string
   *   The page title.
   */
  public function createFormTitle(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
    $group_content_type = GroupContentType::load($plugin->getContentTypeConfigId());

    // The node_types that have a different article than a.
    $node_types = [
      'event' => 'an',
    ];

    // Make sure extensions can change this as well.
    \Drupal::moduleHandler()->alter('social_node_title_prefix_articles', $node_types);

    if ($group_content_type !== NULL && array_key_exists($group_content_type->label(), $node_types)) {
      return $this->t('Create @article @name', [
        '@article' => $node_types[$group_content_type->label()],
        '@name' => $group_content_type->label(),
      ]);
    }

    return $this->t('Create a @name', ['@name' => $group_content_type->label()]);
  }

}
