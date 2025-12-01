<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_event_enrolments_export\Plugin\Action\ExportEnrolments;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Drupal\social_user_export\UserExportService;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports a event enrollment accounts to CSV.
 */
#[Action(
  id: 'social_event_an_enroll_enrolments_export_action',
  label: new TranslatableMarkup('Export the selected enrollments to CSV including anonymous'),
  confirm_form_route_name: 'social_event_managers.vbo.confirm',
  type: 'event_enrollment',
)]
class ExportAllEnrolments extends ExportEnrolments {

  /**
   * The entities that we're executing for.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected array $entities;

  /**
   * Filtered plugin definitions.
   *
   * @var array
   */
  protected array $pluginDefinitions;

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected $socialEventAnEnrollManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a ExportAllEnrolments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_user_export\Plugin\UserExportPluginManager $userExportPlugin
   *   The user export plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user account.
   * @param \Drupal\social_user_export\UserExportService $exportService
   *   The user export service.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserExportPluginManager $userExportPlugin,
    LoggerInterface $logger,
    AccountProxyInterface $currentUser,
    UserExportService $exportService,
    EventAnEnrollManager $social_event_an_enroll_manager,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $userExportPlugin,
      $logger,
      $currentUser,
      $exportService
    );

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;
    $this->entityTypeManager = $entity_type_manager;

    // Get plugin definitions from the service (lazy loading, not in
    // constructor).
    $pluginDefinitions = $this->exportService->getPluginDefinitions();

    $parents = [];

    foreach ($pluginDefinitions as $plugin_id => $plugin_definition) {
      if ($plugin_definition['provider'] === 'social_event_an_enroll_enrolments_export') {
        $parents += class_parents($plugin_definition['class']);
      }
    }

    if ($parents) {
      foreach ($pluginDefinitions as $plugin_id => $plugin_definition) {
        if ($plugin_definition['provider'] !== 'social_event_an_enroll_enrolments_export' && in_array($plugin_definition['class'], $parents)) {
          unset($pluginDefinitions[$plugin_id]);
        }
      }
    }

    // Store filtered plugin definitions for use in getPluginConfiguration.
    $this->pluginDefinitions = $pluginDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.user_export_plugin'),
      $container->get('logger.factory')->get('action'),
      $container->get('current_user'),
      $container->get('social_user_export.user_export'),
      $container->get('social_event_an_enroll.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->entities = $entities;

    // Convert event enrollment entities to user entities.
    // For anonymous enrollments (guests), use the anonymous user (uid 0)
    // instead of NULL to ensure they are included in the export.
    $user_entities = [];
    /** @var \Drupal\social_event\EventEnrollmentInterface $entity */
    foreach ($entities as $entity_id => $entity) {
      if ($this->socialEventAnEnrollManager->isGuest($entity)) {
        // For anonymous enrollments, get the anonymous user (uid 0).
        // Use getAnonymousUser() to ensure proper anonymous user entity.
        $user_entities[$entity_id] = User::getAnonymousUser();
      }
      else {
        // For regular enrollments, get the user account as before.
        $user_entities[$entity_id] = $this->getAccount($entity);
      }
    }

    // Call the export service directly, bypassing the parent
    // ExportEnrolments::executeMultiple() which expects event enrollment
    // entities, not user entities. Use our filtered plugin definitions.
    return $this->exportService->processVboBatch(
      $user_entities,
      $this->pluginDefinitions,
      $this->context,
      [$this, 'getPluginConfiguration']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof EventEnrollmentInterface) {
      if ($this->socialEventAnEnrollManager->isGuest($object)) {
        $access = AccessResult::allowed();
      }
      else {
        $access = $this->getAccount($object)->access('view', $account, TRUE);
      }
    }
    else {
      $access = AccessResult::forbidden();
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration($plugin_id, $entity_id) {
    $configuration = parent::getPluginConfiguration($plugin_id, $entity_id);

    // Always set the enrollment entity if it exists, so plugins can access it.
    // This is especially important for anonymous enrollment plugins that need
    // to access guest information from the enrollment entity.
    if (isset($this->entities[$entity_id])) {
      $configuration['entity'] = $this->entities[$entity_id];
    }

    return $configuration;
  }

}
