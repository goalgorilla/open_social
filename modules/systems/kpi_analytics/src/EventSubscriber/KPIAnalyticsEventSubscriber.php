<?php

namespace Drupal\kpi_analytics\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 *
 * @package Drupal\kpi_analytics\EventSubscriber
 */
class KPIAnalyticsEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * KPIAnalyticsEventSubscriber constructor.
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
  public static function getSubscribedEvents(): array {
    return [
      LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY => [
        'onBuildRender',
        90,
      ],
    ];
  }

  /**
   * Builds render arrays for block plugins and sets it on the event.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component render event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event): void {
    /** @var  \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $event->getPlugin();
    if ($block->getBaseId() !== 'block_content') {
      return;
    }

    /** @var \Drupal\block_content\BlockContentInterface[] $blocks */
    $blocks = $this->entityTypeManager->getStorage('block_content')
      ->loadByProperties(['uuid' => $block->getDerivativeId()]);
    $block_content = reset($blocks);

    if ($block_content->bundle() !== 'kpi_analytics') {
      return;
    }
    $build = $event->getBuild();
    $component = $event->getComponent();
    $uuid = $component->get('uuid');
    $content = &$build['content'];
    $content['kpi_analytics'] = [
      '#lazy_builder' => [
        'kpi_analytics.kpi_builder:build',
        [
          $block_content->getEntityTypeId(),
          $block_content->id(),
          $uuid,
        ],
      ],
      '#create_placeholder' => TRUE,
    ];

    $event->setBuild($build);
  }

}
