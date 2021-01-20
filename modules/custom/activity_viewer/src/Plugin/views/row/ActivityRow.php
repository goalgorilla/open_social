<?php

namespace Drupal\activity_viewer\Plugin\views\row;

use Drupal\activity_creator\Plugin\ActivityDestinationManager;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Plugin\views\row\EntityRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which performs a activity_view on the resulting object.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:activity",
 * )
 */
class ActivityRow extends EntityRow {

  /**
   * Activity destination manager.
   *
   * @var \Drupal\activity_creator\Plugin\ActivityDestinationManager
   */
  protected $activityDestinationManager;

  /**
   * ActivityRow constructor.
   *
   * @param array $configuration
   *   The config.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\activity_creator\Plugin\ActivityDestinationManager $activity_destination_manager
   *   The activity destination manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, EntityRepositoryInterface $entity_repository, EntityDisplayRepositoryInterface $entity_display_repository, ActivityDestinationManager $activity_destination_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $entity_repository, $entity_display_repository);
    $this->activityDestinationManager = $activity_destination_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('entity.repository'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.activity_destination.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {

    $view_mode = $this->options['view_mode'];

    if ($result) {
      foreach ($result as $row) {
        $render_result = [];
        $render_result[] = $row;
        $entity = $row->_entity;

        foreach ($entity->field_activity_destinations as $destination) {
          /** @var \Drupal\activity_creator\Plugin\ActivityDestinationBase $plugin */
          $plugin = $this->activityDestinationManager->createInstance($destination->value);
          if ($plugin->isActiveInView($this->view)) {
            $this->options['view_mode'] = $plugin->getViewMode($view_mode, $entity);
          }
        }
        $this->getEntityTranslationRenderer()->preRender($render_result);
      }
    }
    $this->options['view_mode'] = $view_mode;
  }

}
