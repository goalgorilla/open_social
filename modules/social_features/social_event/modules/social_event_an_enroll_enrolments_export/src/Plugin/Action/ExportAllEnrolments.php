<?php

namespace Drupal\social_event_an_enroll_enrolments_export\Plugin\Action;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_user_export\Plugin\Action\ExportUser;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileRepository;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_event_enrolments_export\Plugin\Action\ExportEnrolments;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exports a event enrollment accounts to CSV.
 *
 * @Action(
 *   id = "social_event_an_enroll_enrolments_export_action",
 *   label = @Translation("Export the selected enrollments to CSV including anonymous"),
 *   type = "event_enrollment",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event_managers.vbo.confirm",
 * )
 */
class ExportAllEnrolments extends ExportEnrolments {

  /**
   * The entities that we're executing for.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected array $entities;

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected EventAnEnrollManager $socialEventAnEnrollManager;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory for the export plugin access.
   * @param \Drupal\file\FileRepository $file_repository
   *   The file repository service.
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file url generator service.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserExportPluginManager $userExportPlugin,
    LoggerInterface $logger,
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    FileRepository $file_repository,
    FileUrlGenerator $file_url_generator,
    EventAnEnrollManager $social_event_an_enroll_manager,
    FileSystem $file_system,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $userExportPlugin,
      $logger,
      $currentUser,
      $configFactory,
      $file_url_generator,
      $file_repository,
      $file_system,
    );

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;

    $parents = [];

    foreach ($this->pluginDefinitions as $plugin_id => $plugin_definition) {
      if ($plugin_definition['provider'] === 'social_event_an_enroll_enrolments_export') {
        $parents += class_parents($plugin_definition['class']);
      }
    }

    if ($parents) {
      foreach ($this->pluginDefinitions as $plugin_id => $plugin_definition) {
        if ($plugin_definition['provider'] !== 'social_event_an_enroll_enrolments_export' && in_array($plugin_definition['class'], $parents)) {
          unset($this->pluginDefinitions[$plugin_id]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ExportUser|ContainerFactoryPluginInterface|static {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.user_export_plugin'),
      $container->get('logger.factory')->get('action'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('file.repository'),
      $container->get('file_url_generator'),
      $container->get('social_event_an_enroll.manager'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities): array {
    $this->entities = $entities;

    return parent::executeMultiple($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
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
  public function getPluginConfiguration($plugin_id, $entity_id): array {
    $configuration = parent::getPluginConfiguration($plugin_id, $entity_id);
    $plugin_definition = &$this->pluginDefinitions[$plugin_id];

    foreach ($this->pluginDefinitions as $plugin_definition) {
      if (($plugin_definition['id'] === $plugin_id) && $plugin_definition['provider'] === 'social_event_an_enroll_enrolments_export') {
        $configuration['entity'] = $this->entities[$entity_id];
        break;
      }
    }

    return $configuration;
  }

}
