<?php

namespace Drupal\social_event_an_enroll\Plugin\ContentExportPlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_content_export\Plugin\ContentExportPluginBase;
use Drupal\social_event_an_enroll\EventAnEnrollService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ContentEventEnrolleesAn' content export row.
 *
 * @ContentExportPlugin(
 *   id = "content_event_enrollees_an",
 *   label = @Translation("Enrollees Anonymous"),
 *   weight = -110,
 * )
 */
class ContentEventEnrolleesAn extends ContentExportPluginBase {

  /**
   * The event enroll service.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollService
   */
  protected $eventAnEnrollService;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs of the ContentEventEnrolleesAn class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollService $event_an_enroll_service
   *   The event enroll service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $database, EventAnEnrollService $event_an_enroll_service, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $database);
    $this->eventAnEnrollService = $event_an_enroll_service;
    $this->moduleHandler = $module_handler;
  }

  /**
   * The create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('social_event_an_enroll.service'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(NodeInterface $entity): string {
    if ($entity->getType() == 'event') {
      if ($this->moduleHandler->moduleExists('social_event_an_enroll')) {
        return (string) $this->eventAnEnrollService->enrollmentCount((int) $entity->id());
      }
    }

    return '';
  }

}
