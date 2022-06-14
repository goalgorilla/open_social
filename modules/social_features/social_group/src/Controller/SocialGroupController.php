<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_group\SocialGroupInterface;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Group routes.
 */
class SocialGroupController extends ControllerBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * The private temporary storage factory.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The Views Bulk Operations action processor.
   */
  protected ViewsBulkOperationsActionProcessorInterface $actionProcessor;

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * The currently active route match object.
   */
  private RouteMatchInterface $routeMatch;

  /**
   * SocialGroupController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $action_processor
   *   The Views Bulk Operations action processor.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    RequestStack $request_stack,
    PrivateTempStoreFactory $temp_store_factory,
    ViewsBulkOperationsActionProcessorInterface $action_processor,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->requestStack = $request_stack;
    $this->tempStoreFactory = $temp_store_factory;
    $this->actionProcessor = $action_processor;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('request_stack'),
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * The _title_callback for the view.group_members.page_group_members route.
   *
   * @param \Drupal\social_group\SocialGroupInterface|int $group
   *   The group ID.
   */
  public function groupMembersTitle($group): ?TranslatableMarkup {
    // If it's not a group then it's a gid.
    if (!$group instanceof SocialGroupInterface) {
      $group = $this->entityTypeManager()->getStorage('group')->load($group);
    }

    return $group instanceof SocialGroupInterface
      ? $this->t('Members of @name', ['@name' => $group->label()]) : NULL;
  }

  /**
   * The _title_callback for the view.posts.block_stream_group route.
   *
   * @param \Drupal\social_group\SocialGroupInterface $group
   *   The group entity object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   The page title.
   */
  public function groupStreamTitle(SocialGroupInterface $group) {
    return $group->label();
  }

  /**
   * Callback function of the stream page of a group.
   */
  public function groupStream(): array {
    return ['#markup' => ''];
  }

  /**
   * The title callback for the entity.group_content.add_form.
   */
  public function groupAddMemberTitle(): TranslatableMarkup {
    $group_content = $this->routeMatch->getParameter('group_content');
    $group = $this->routeMatch->getParameter('group');

    if (
      $group_content instanceof GroupContentInterface &&
      $group_content->getGroupContentType()->getContentPluginId() === 'group_invitation'
    ) {
      if ($group instanceof SocialGroupInterface) {
        return $this->t('Add invites to group: @group_name', [
          '@group_name' => $group->label(),
        ]);
      }

      return $this->t('Add invites');
    }

    if ($group instanceof SocialGroupInterface) {
      return $this->t('Add members to group: @group_name', [
        '@group_name' => $group->label(),
      ]);
    }

    return $this->t('Add members');
  }

  /**
   * The title callback for the entity.group_content.delete-form.
   */
  public function groupRemoveContentTitle(): TranslatableMarkup {
    $group_content = $this->routeMatch->getParameter('group_content');

    if (
      $group_content instanceof GroupContentInterface &&
      $group_content->getGroupContentType()->getContentPluginId() === 'group_invitation'
    ) {
      $group = $this->routeMatch->getParameter('group');

      if ($group instanceof SocialGroupInterface) {
        return $this->t('Remove invitee from group: @group_name', [
          '@group_name' => $group->label(),
        ]);
      }

      return $this->t('Remove invitation');
    }

    return $this->t('Remove a member');
  }

  /**
   * Method that checks access on the my groups pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account we need to check access for.
   */
  public function myGroupAccess(AccountInterface $account): AccessResultInterface {
    if (($request = $this->requestStack->getCurrentRequest()) === NULL) {
      return AccessResult::neutral();
    }

    // Fetch user from url.
    $user = $request->get('user');

    // If we don't have a user in the request, assume it's my own profile.
    if (is_null($user)) {
      // Usecase is the user menu, which is generated on all LU pages.
      $user = $this->entityTypeManager()->getStorage('user')
        ->load($account->id());
    }

    // If not a user then just return neutral.
    if (!$user instanceof UserInterface) {
      $user = $this->entityTypeManager()->getStorage('user')->load($user);

      if (!$user instanceof UserInterface) {
        return AccessResult::neutral();
      }
    }

    if ($user->isBlocked()) {
      return AccessResult::allowedIfHasPermission($account, 'view blocked user');
    }

    return AccessResult::allowedIfHasPermission(
      $account,
      // Own profile?
      $user->id() === $account->id()
        ? 'view groups on other profiles' : 'view groups on my profile',
    );
  }

  /**
   * Redirects users to their groups page.
   */
  public function redirectMyGroups(): RedirectResponse {
    return $this->redirect('view.groups.page_user_groups', [
      'user' => $this->currentUser()->id(),
    ]);
  }

  /**
   * Redirects users to the main group page.
   *
   * @param int $group
   *   The group entity identifier.
   */
  public function otherGroupPage(int $group): RedirectResponse {
    return $this->redirect('entity.group.canonical', ['group' => $group]);
  }

}
