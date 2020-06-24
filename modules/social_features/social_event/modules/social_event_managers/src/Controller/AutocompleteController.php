<?php

namespace Drupal\social_event_managers\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AutocompleteController.
 *
 * @package Drupal\social_event_managers\Controller
 */
class AutocompleteController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialTopicController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
   * Checks access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   Current route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function access(AccountInterface $account, RouteMatch $route_match) {
    // Check if the user is member of the group.
    $group = $route_match->getParameter('group');
    $group = $this->entityTypeManager->getStorage('group')
      ->load($group);

    if ($group instanceof GroupInterface) {
      $is_member = $group->getMember($account) instanceof GroupMembershipLoaderInterface;
      // Invert this.
      if (!$is_member) {
        // Also check if the person is event owner or organizer
        $hasPermissionIsOwnerOrOrganizer = social_event_owner_or_organizer();
        return AccessResult::allowedIf($hasPermissionIsOwnerOrOrganizer === TRUE);
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Get all the members from a group where the event is in,
   * and strip out people who are already enrolled.
   */
  public function populate ($node, $group) {
    // Create the dataset to send at the end.
    $data = [];

    // Get all the members from the group.
    $group = $this->entityTypeManager->getStorage('group')
      ->load($group);

    if ($group instanceof GroupInterface) {
      $memberships = $group->getMembers();
      /** @var \Drupal\social_event\EventEnrollmentStatusHelper $enrollments */
      $enrollments = \Drupal::service('social_event.status_helper');

      foreach ($memberships as $membership) {
       $user_id = $membership->getUser()->id();
       $user = User::load($user_id);

       // Or should we get all the enrollments and include them in the foreach?
       if($enrollments->getEventEnrollments($user_id, $node, TRUE)) {
         // If the user already has an enrollments, skip it.
         continue;
       };
       $display_name = $user->getDisplayName();
       $display_name = "$display_name ($user_id)";

        $data[] = [
         'id' => $membership->getUser()->id(),
         'full_name' => $display_name
       ];
      }
    }

    if (!empty($data)) {
      usort($data, [$this ,'sort_alphabetically']);
      $response = new AjaxResponse();
      $response->setData($data);

      return $response;
    }
  }

  /**
   * Enroll
   */
  private static function sort_alphabetically($a,$b) {
    return ($a['full_name'] >= $b['full_name']) ? -1 : 1;
  }
}
