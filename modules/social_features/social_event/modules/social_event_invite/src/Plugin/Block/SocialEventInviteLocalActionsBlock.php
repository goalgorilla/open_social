<?php

namespace Drupal\social_event_invite\Plugin\Block;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event_invite\SocialEventInviteAccessHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'SocialEventInviteLocalActionsBlock' block.
 *
 * @Block(
 *  id = "social_event_invite_block",
 *  admin_label = @Translation("Social Event Invite block"),
 * )
 */
class SocialEventInviteLocalActionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The event invite access helper.
   *
   * @var \Drupal\social_event_invite\SocialEventInviteAccessHelper
   */
  protected $accessHelper;

  /**
   * EventAddBlock constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\social_event_invite\SocialEventInviteAccessHelper $accessHelper
   *   The event invite access helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, SocialEventInviteAccessHelper $accessHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->accessHelper = $accessHelper;
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
      $container->get('social_event_invite.access_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    try {
      return $this->accessHelper->eventFeatureAccess();
    }
    catch (InvalidPluginDefinitionException $e) {
      return AccessResult::neutral();
    }
    catch (PluginNotFoundException $e) {
      return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $cache_contexts[] = 'user';
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Get current group so we can build correct links.
    $event = $this->routeMatch->getParameter('node');
    $event = Node::load($event);

    // Todo:: not needed because we get parameter from node anyway?
    if ($event instanceof NodeInterface) {
      $links = [
        '#type' => 'dropbutton',
        '#attributes' => [
          'no-split' => [
            'title' => $this->t('Add enrollees'),
          ],
        ],
        '#links' => [
          'add_directly' => [
            'title' => $this->t('Add directly'),
            'url' => Url::fromRoute('social_event_invite.invite_user', ['node' => $event->id()]),
          ],
          'invite_by_mail' => [
            'title' => $this->t('Invite by email'),
            'url' => Url::fromRoute('social_event_invite.invite_email', ['node' => $event->id()]),
          ],
          'view_invites' => [
            'title' => $this->t('View invites'),
            'url' => Url::fromRoute('view.event_manage_enrollment_invites.page_manage_enrollment_invites', ['node' => $event->id()]),
          ],
        ],
      ];

      $build['content'] = $links;
    }

    return $build;
  }

}
