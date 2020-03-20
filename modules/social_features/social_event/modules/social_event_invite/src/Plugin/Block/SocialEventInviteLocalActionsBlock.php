<?php

namespace Drupal\social_event_invite\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}.
   */
  protected function blockAccess(AccountInterface $account) {
    // If current Group doesn't allow for inviting.
    if () {
      return AccessResult::forbidden();
    }
    // Only when user has correct access.
    if () {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
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
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $group = $this->routeMatch->getParameter('group');

    if ($group instanceof GroupInterface) {
      $cache_tags[] = 'group:' . $group->id();
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Get current group so we can build correct links.
    $event = $this->routeMatch->getParameter('node');
    if ($event instanceof NodeInterface) {
      $links = [
        '#type' => 'dropbutton',
        '#links' => [
          'add_directly' => [
            'title' => $this->t('Add directly'),
            'url' => Url::fromRoute('entity.group_content.add_form', ['plugin_id' => 'group_membership', 'event' => $event->id()]),
          ],
          'invite_by_mail' => [
            'title' => $this->t('Invite by mail'),
            'url' => Url::fromRoute('ginvite.invitation.bulk', ['group' => $event->id()]),
          ],
          'view_invites' => [
            'title' => $this->t('View invites'),
            'url' => Url::fromRoute('view.group_invitations.page_1', ['group' => $event->id()]),
          ],
        ],
      ];

      $build['content'] = $links;
    }

    return $build;
  }

}