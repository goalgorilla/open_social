<?php

namespace Drupal\social_event_invite\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_group\SocialGroupHelperService;
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\social_group\SocialGroupHelperService $groupHelperService
   *   The group helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, ConfigFactoryInterface $configFactory, SocialGroupHelperService $groupHelperService, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->configFactory = $configFactory;
    $this->groupHelperService = $groupHelperService;
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('config.factory'),
      $container->get('social_group.helper_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}.
   */
  protected function blockAccess(AccountInterface $account) {
    $config = $this->configFactory->get('social_event_invite.settings');
    $enabled_global = $config->get('invite_enroll');

    // If it's globally disabled, we don't want to show the block.
    if (!$enabled_global) {
      return AccessResult::forbidden();
    }

    // Get the group of this node.
    $node_id = $this->routeMatch->getParameter('node');
    $gid_from_entity = $this->groupHelperService->getGroupFromEntity([
      'target_type' => 'node',
      'target_id' => $node_id,
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

      // If the group manager of the group this event belongs to decided that this
      // feature is enabled, we don't want to show the block.
      if ($config->get('invite_group_controllable') && !$group->get('event_invite')) {
        return AccessResult::forbidden();
      }
    }

    // If we've got this far we can be sure the user is allowed to see this
    // block.
    return AccessResult::allowed();
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
        '#links' => [
          'title' => [
            'title' => $this->t('Invite'),
            'url' => Url::fromRoute('<current>', []),
          ],
          'add_directly' => [
            'title' => $this->t('Add directly'),
            'url' => Url::fromRoute('social_event_invite.invite_user', ['node' => $event->id()]),
          ],
          'invite_by_mail' => [
            'title' => $this->t('Invite by mail'),
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
