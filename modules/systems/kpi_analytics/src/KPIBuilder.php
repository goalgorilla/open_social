<?php

namespace Drupal\kpi_analytics;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\kpi_analytics\Plugin\KPIDataFormatterManager;
use Drupal\kpi_analytics\Plugin\KPIDatasourceManager;
use Drupal\kpi_analytics\Plugin\KPIVisualizationManager;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * The KPIBuilder class.
 *
 * @package Drupal\kpi_analytics
 */
class KPIBuilder implements KPIBuilderInterface, TrustedCallbackInterface {

  /**
   * The entity repository.
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The kpi datasource manager.
   */
  protected KPIDatasourceManager $kpiDatasourceManager;

  /**
   * The kpi dataformatter manager.
   */
  protected KPIDataFormatterManager $kpiDataFormatterManager;

  /**
   * The kpi visualization manager.
   */
  protected KPIVisualizationManager $kpiVisualizationManager;

  /**
   * The section storage manager.
   */
  protected SectionStorageManagerInterface $sectionStorageManager;

  /**
   * The current route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * KPIBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\kpi_analytics\Plugin\KPIDatasourceManager $kpi_datasource_manager
   *   The kpi datasource manager.
   * @param \Drupal\kpi_analytics\Plugin\KPIDataFormatterManager $kpi_data_formatter_manager
   *   The kpi dataformatter manager.
   * @param \Drupal\kpi_analytics\Plugin\KPIVisualizationManager $kpi_visualization_manager
   *   The kpi visualization manager.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeManagerInterface $entity_type_manager,
    KPIDatasourceManager $kpi_datasource_manager,
    KPIDataFormatterManager $kpi_data_formatter_manager,
    KPIVisualizationManager $kpi_visualization_manager,
    SectionStorageManagerInterface $section_storage_manager,
    RouteMatchInterface $route_match
  ) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->kpiDatasourceManager = $kpi_datasource_manager;
    $this->kpiDataFormatterManager = $kpi_data_formatter_manager;
    $this->kpiVisualizationManager = $kpi_visualization_manager;
    $this->sectionStorageManager = $section_storage_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function build($entity_type_id, $entity_id, $block_id = NULL): array {
    /** @var \Drupal\block_content\Entity\BlockContent $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $entity = $this->entityRepository->getTranslationFromContext($entity);

    /** @var \Drupal\block\BlockInterface $block */
    if ($block_id) {
      $block = $this->entityTypeManager->getStorage('block')->load($block_id);
      if (!$block) {
        if ($route_entity = $this->getRouteEntity()) {
          /** @var \Drupal\layout_builder\SectionStorageInterface $section_storage */
          $section_storage = $this->getSectionStorageForEntity($route_entity);
        }
        else {
          $section_storage = $this->routeMatch->getParameter('section_storage');
        }
        if ($section_storage instanceof SectionStorageInterface) {
          $sections = $section_storage->getSections();
          foreach ($sections as $section) {
            try {
              $block = $section->getComponent($block_id);
              break;
            }
            catch (\InvalidArgumentException $e) {
              continue;
            }
          }
        }
      }
    }
    else {
      $block = NULL;
    }

    $datasource = $entity->field_kpi_datasource->value;
    $datasource_plugin = $this->kpiDatasourceManager->createInstance($datasource);
    $data = $datasource_plugin->query($entity, $block);

    $data_formatters = $entity->field_kpi_data_formatter->getValue();
    foreach ($data_formatters as $data_formatter) {
      $data_formatter_plugin = $this->kpiDataFormatterManager->createInstance($data_formatter['value']);
      $data = $data_formatter_plugin->format($data, $block);
    }

    $visualization = $entity->field_kpi_visualization->value;
    // Retrieve the plugins.
    $visualization_plugin = $this->kpiVisualizationManager->createInstance($visualization);

    $labels = array_map(static fn($item) => $item['value'], $entity->get('field_kpi_chart_labels')->getValue());

    $colors = array_map(static fn($item) => $item['value'], $entity->get('field_kpi_chart_colors')->getValue());

    return $visualization_plugin
      ->setLabels($labels)
      ->setColors($colors)
      ->render($data);
  }

  /**
   * Gets the section storage for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage if found otherwise NULL.
   */
  protected function getSectionStorageForEntity(EntityInterface $entity): ?SectionStorageInterface {
    // @todo Take into account other view modes in
    //   https://www.drupal.org/node/3008924.
    $view_mode = 'full';

    if ($entity instanceof LayoutEntityDisplayInterface) {
      $contexts['display'] = EntityContext::fromEntity($entity);
      $contexts['view_mode'] = new Context(new ContextDefinition('string'), $entity->getMode());
    }
    else {
      $contexts['entity'] = EntityContext::fromEntity($entity);
      if ($entity instanceof FieldableEntityInterface) {
        $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
        if ($display instanceof LayoutEntityDisplayInterface) {
          $contexts['display'] = EntityContext::fromEntity($display);
        }
        $contexts['view_mode'] = new Context(new ContextDefinition('string'), $view_mode);
      }
    }
    return $this->sectionStorageManager->findByContext($contexts, new CacheableMetadata());
  }

  /**
   * Gets the entity for current route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The object of entity if found otherwise NULL.
   */
  protected function getRouteEntity(): ?ContentEntityInterface {
    if (
      ($route = $this->routeMatch->getRouteObject()) &&
      ($parameters = $route->getOption('parameters'))
    ) {
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $this->routeMatch->getParameter($name);
          if ($entity instanceof ContentEntityInterface) {
            return $entity;
          }
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['build'];
  }

}
