<?php

namespace Drupal\social_user\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @return RedirectResponse
   *   Return Redirect to the user account.
   */
  public function otherUserPage(UserInterface $user) {
    return $this->redirect('entity.user.canonical', ['user' => $user->id()]);
  }

  /**
   * The _title_callback for the users profile stream title.
   *
   * @return string
   *   The first and/or last name with the AccountName as a fallback.
   */
  public function setUserStreamTitle(UserInterface $user = NULL) {
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
   * @param \Drupal\Core\Routing\RouteMatch $routeMatch
   *   The matched route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessUsersPages(AccountInterface $account, RouteMatch $routeMatch) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $routeMatch->getParameter('user');
    if (isset($user)) {
      if (!$user instanceof UserInterface) {
        $user = $this->entityTypeManager->getStorage('user')
          ->load($user);
      }
    }
    else {
      return AccessResult::neutral();
    }

    if ($user->isBlocked()) {
      if ($account->hasPermission('view blocked user')) {
        return AccessResult::allowed();
      }
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
