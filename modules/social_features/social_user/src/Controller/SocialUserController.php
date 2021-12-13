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
 * Returns responses for User routes.
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

  /**
   * Returns titles list of all groups, ordered by their type and/or label.
   *
   * @param bool $split
   *   (optional) TRUE if groups should be split by type. Defaults to FALSE.
   */
  public static function getGroups(bool $split = FALSE): array {
    if (!empty($data = &drupal_static('_social_user_get_groups', []))) {
      return $data;
    }

    $query = \Drupal::database()->select('groups_field_data', 'gfd')
      ->fields('gfd', ['id', 'label']);

    if ($split) {
      $query->addField('gfd', 'type');
      $query->orderBy('type');
    }

    if (
      ($query = $query->orderBy('label')->execute()) === NULL ||
      !($groups = $split ? $query->fetchAll() : $query->fetchAllKeyed())
    ) {
      return $data;
    }

    if ($split) {
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo('group');

      foreach ($groups as $group) {
        $data[$bundles[$group->type]['label']][$group->id] = $group->label;
      }
    }
    else {
      $data = $groups;
    }

    return $data;
  }

  /**
   * Returns titles list of all groups, ordered by their type and label.
   */
  public static function getSplitGroups(): array {
    return static::getGroups(TRUE);
  }

}
