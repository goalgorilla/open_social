<?php

namespace Drupal\social_group_invite\Plugin\Menu\LocalTask;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ginvite\GroupInvitationLoaderInterface;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a local task that shows the amount of group invites.
 */
class GroupInviteLocalTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Group invitations loader.
   *
   * @var \Drupal\ginvite\GroupInvitationLoaderInterface
   */
  protected $invitationLoader;

  /**
   * Construct the UnapprovedComments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\ginvite\GroupInvitationLoaderInterface $invitationLoader
   *   The invite loader.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $routeMatch, GroupInvitationLoaderInterface $invitationLoader) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->invitationLoader = $invitationLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('ginvite.invitation_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    if ($this->invitationLoader->loadByUser()) {
      // We don't need plural because users will be redirected
      // if there is no invite.
      return $this->t('Group invites (@count)', ['@count' => count($this->invitationLoader->loadByUser())]);
    }

    return $this->t('Group invites');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = [];
    $user = $this->routeMatch->getParameter('user');

    // Add cache tags for group invite.
    // These are cleared based on entity:user->id
    // for the group content plugin group_invitation.
    if ($user instanceof UserInterface) {
      $tags[] = 'group_content_list:entity:' . $user->id();
      $tags[] = 'group_content_list:plugin:group_invitation:entity:' . $user->id();
    }
    if (is_string($user)) {
      $tags[] = 'group_content_list:entity:' . $user;
      $tags[] = 'group_content_list:plugin:group_invitation:entity:' . $user;
    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

}
