<?php

namespace Drupal\social_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SocialUserController.
 *
 * @package Drupal\social_user\Controller
 */
class SocialUserController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialUserController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * OtherUserPage.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Return Redirect to the user account.
   */
  public function otherUserPage(UserInterface $user): RedirectResponse {
    return $this->redirect('entity.user.canonical', ['user' => $user->id()]);
  }

  /**
   * The _title_callback for the users profile stream title.
   *
   * @return string
   *   The first and/or last name with the AccountName as a fallback.
   */
  public function setUserStreamTitle(?UserInterface $user = NULL) {
    if ($user instanceof UserInterface) {
      return $user->getDisplayName();
    }
  }

  /**
   * Checks access for a user list page request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer users',
      'view users',
    ], 'OR');
  }

  /**
   * Checks access for user page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The matched route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function accessUsersPages(AccountInterface $account, RouteMatchInterface $routeMatch): AccessResultInterface {
    $user = $routeMatch->getParameter('user');
    if ($user === NULL) {
      // Parameter can exist in "views" pages.
      $user = $routeMatch->getParameter('uid');
    }

    if ($user === NULL) {
      // If user doesn't exist we can do anything.
      return AccessResult::neutral();
    }

    if (is_numeric($user)) {
      $user = $this->entityTypeManager->getStorage('user')
        ->load($user);
    }

    if (!$user instanceof UserInterface) {
      // If user doesn't exist we can do anything.
      return AccessResult::neutral();
    }

    // Make sure the current user has access to see blocked users.
    if ($user->isBlocked()) {
      if ($account->hasPermission('view blocked user')) {
        return AccessResult::allowed();
      }

      return AccessResult::forbidden();
    }

    // If the current user has ony of these permissions, we allow the access.
    if (
      $account->hasPermission('view any profile profile') ||
      $account->hasPermission('access user profiles')
    ) {
      return AccessResult::allowed();
    }

    // The current user should have access to own pages.
    if (
      $account->id() === $user->id() &&
      $account->hasPermission('view own profile profile')
    ) {
      return AccessResult::allowed();
    }

    // Restrict the access to a user page.
    return AccessResult::forbidden();
  }

  /**
   * Redirects users from /my-profile to stream page of current user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the stream page of current user.
   */
  public function myProfileRedirect(): RedirectResponse {
    return $this->redirect('social_user.stream', [
      'user' => $this->currentUser()->id(),
    ]);
  }

}
