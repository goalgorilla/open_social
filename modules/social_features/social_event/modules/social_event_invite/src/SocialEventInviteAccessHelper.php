<?php

namespace Drupal\social_event_invite;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\SocialGroupHelperService;

/**
 * Class SocialEventInviteAccessHelper
 *
 * @package Drupal\social_event_invite\Access
 */
class SocialEventInviteAccessHelper {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $groupHelperService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * EventInvitesAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\social_group\SocialGroupHelperService $groupHelperService
   *   The group helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(RouteMatchInterface $routeMatch, ConfigFactoryInterface $configFactory, SocialGroupHelperService $groupHelperService, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser) {
    $this->routeMatch = $routeMatch;
    $this->configFactory = $configFactory;
    $this->groupHelperService = $groupHelperService;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
  }

  /**
   * Custom access check for the event invite features for event managers.
   *
   * @return \Drupal\Core\Access\AccessResult
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function eventFeatureAccess() {
    $config = $this->configFactory->get('social_event_invite.settings');
    $enabled_global = $config->get('invite_enroll');

    // If it's globally disabled, we don't want to show the block.
    if (!$enabled_global) {
      return AccessResult::forbidden();
    }

    // Get the group of this node.
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      $node = $node->id();
    }
    $gid_from_entity = $this->groupHelperService->getGroupFromEntity([
      'target_type' => 'node',
      'target_id' => $node,
    ]);

    // If we have a group we need to additional checks.
    if ($gid_from_entity !== NULL) {
      /* @var $group \Drupal\group\Entity\GroupInterface */
      $group = $this->entityTypeManager
        ->getStorage('group')
        ->load($gid_from_entity);

      $enabled_for_group = $config->get('invite_group_types');
      $enabled = FALSE;
      if (is_array($enabled_for_group)) {
        foreach ($enabled_for_group as $group_type) {
          if ($group_type === $group->bundle()) {
            $enabled = TRUE;
            break;
          }
        }
      }

      // If it's not enabled for the group this event belongs to, we don't want to
      // show the block.
      if (!$enabled) {
        return AccessResult::forbidden();
      }
    }

    // If we've got this far we can be sure the user is allowed to see this
    // block.
    // @todo: move that function to a service.
    return AccessResult::allowedIf(social_event_owner_or_organizer());
  }

  /**
   * Custom access check for the user invite overview.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function userInviteAccess() {
    $config = $this->configFactory->get('social_event_invite.settings');
    $enabled_global = $config->get('invite_enroll');

    // If it's globally disabled, we don't want to show the block.
    if (!$enabled_global) {
      return AccessResult::forbidden();
    }

    // Get the user.
    $account = $this->routeMatch->getParameter('user');
    if ($account instanceof AccountProxyInterface) {
      /* @var $account \Drupal\Core\Session\AccountProxyInterface */
      $account = $account->id();
      return AccessResult::allowedIf($account->id() === $this->currentUser->id());
    }

    return AccessResult::neutral();
  }
}
