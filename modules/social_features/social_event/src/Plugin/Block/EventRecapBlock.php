<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_event\Entity\Node\EventInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display the event recording media.
 */
#[Block(
  id: "event_recap_block",
  admin_label: new TranslatableMarkup("Event Recap"),
  context_definitions: [
    'node' => new EntityContextDefinition(
      data_type: 'entity:node',
      label: new TranslatableMarkup("Event"),
      required: TRUE,
    ),
  ]
)]
class EventRecapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an EventRecordingBlock.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    if ($this->routeMatch->getRouteName() !== 'entity.node.canonical') {
      return AccessResult::forbidden()
        ->addCacheContexts(['route']);
    }

    $node = $this->getContextValue('node');
    if (!$node instanceof EventInterface) {
      return AccessResult::forbidden()
        ->addCacheContexts(['route']);
    }

    if (!$node->hasEventMeetingRecording()) {
      return AccessResult::forbidden()
        ->addCacheableDependency($node)
        ->addCacheContexts(['route']);
    }

    // CM+, author and enrollees should have access.
    $has_access = $account->hasPermission('administer nodes')
      || $node->isEventManager($account)
      || $node->getParticipation($account);

    return AccessResult::allowedIf($has_access)
      ->addCacheableDependency($node)
      ->addCacheTags($this->getCacheTags())
      ->cachePerUser()
      ->addCacheContexts(['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->getContextValue('node');

    if (!$node instanceof EventInterface) {
      return [];
    }

    $media = $node->getEventMeetingRecording();

    if ($media === NULL) {
      return [];
    }

    $build = $this->entityTypeManager
      ->getViewBuilder('media')
      ->view($media, 'event');

    $build['#cache']['tags'] = Cache::mergeTags(
      $build['#cache']['tags'] ?? [],
      $node->getCacheTags(),
      $media->getCacheTags(),
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $node = $this->getContextValue('node');
    if (!$node instanceof EventInterface) {
      return parent::getCacheTags();
    }

    return Cache::mergeTags(
      parent::getCacheTags(),
      ['event_enrollment_list:' . $node->id()],
    );
  }

}
