<?php

namespace Drupal\social_group_quickjoin\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\social_group\SocialGroupInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SocialQuickJoinController.
 *
 * @package Drupal\social_group_quickjoin\Controller
 */
class SocialQuickJoinController extends ControllerBase {

  use MessengerTrait;

  /**
   * The request.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialEventController constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRoute
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(CurrentRouteMatch $currentRoute, ConfigFactoryInterface $configFactory) {
    $this->currentRoute = $currentRoute;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * Function that add the current user to a group without confirmation step.
   *
   * @param \Drupal\social_group\SocialGroupInterface $group
   *   The group you want to join.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function quickJoin(SocialGroupInterface $group): RedirectResponse {
    // It's a group, so determine the path for redirection.
    $groupRedirect = $group->toUrl()->toString();
    $settings = $this->configFactory->get('social_group_quickjoin.settings');

    // Check if the settings are active.
    $active = $settings->get('social_group_quickjoin_enabled');

    // Not active, so back to group canonical.
    if (!$active) {
      // Redirect to the group.
      return new RedirectResponse($groupRedirect);
    }

    // Check if the current group type is enabled.
    if (!$settings->get('social_group_quickjoin_' . $group->getGroupType()->id())) {
      $this->messenger()->addMessage($this->t("You can't join this group directly."));
      return new RedirectResponse($groupRedirect);
    }

    // With a join method, we need to be sure it allows for direct joining.
    if ($group->hasField('field_group_allowed_join_method') &&
      !empty($group->get('field_group_allowed_join_method')->value)) {
      $method = $group->get('field_group_allowed_join_method')->value;
      if ($method !== 'direct') {
        $this->messenger()->addMessage($this->t("You can't join this group directly."));
        return new RedirectResponse($groupRedirect);
      }
    }

    // Already a member.
    if ($group->hasMember($this->currentUser())) {
      // Set a message.
      $this->messenger()->addMessage($this->t("You're already a member of this group."));
      // Redirect to the group.
      return new RedirectResponse($groupRedirect);
    }

    // Fetch the user from the accountproxy.
    $account = User::load($this->currentUser()->id());
    if ($account instanceof UserInterface) {
      // Extra exceptions based on groupmembership rules.
      if ($group->hasPermission('join group', $account) === FALSE) {
        $this->messenger()->addMessage($this->t("You don't have access to join this group."));
        return new RedirectResponse($groupRedirect);
      }

      // Add this person to the group.
      $group->addMember($account);
      // Invalidate cache of the group, so we see newest member block updated.
      $this->cache()->invalidate('group:' . $group->id());
      // Set message and redirect.
      $this->messenger()->addMessage($this->t("You've been added to this group."));
      return new RedirectResponse($groupRedirect);
    }

    // Weird behaviour if here, so just go to group home.
    return new RedirectResponse($groupRedirect);
  }

}
