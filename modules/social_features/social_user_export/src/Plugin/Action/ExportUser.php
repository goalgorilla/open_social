<?php

namespace Drupal\social_user_export\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\social_user_export\Plugin\UserExportPluginManager;
use Drupal\social_user_export\UserExportService;
use Drupal\user\UserInterface;

/**
 * Exports a user accounts to CSV.
 */
#[Action(
  id: 'social_user_export_user_action',
  label: new TranslatableMarkup('Export the selected users to CSV'),
  confirm_form_route_name: 'views_bulk_operations.confirm',
  type: 'user',
)]
class ExportUser extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {
  use MessengerTrait;

  /**
   * The User export plugin manager.
   *
   * @var \Drupal\social_user_export\Plugin\UserExportPluginManager
   */
  protected UserExportPluginManager $userExportPlugin;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The user export service.
   *
   * @var \Drupal\social_user_export\UserExportService
   */
  protected UserExportService $exportService;

  /**
   * Constructs a ExportUser object.
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserExportPluginManager $userExportPlugin,
    LoggerInterface $logger,
    AccountProxyInterface $currentUser,
    UserExportService $exportService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userExportPlugin = $userExportPlugin;
    $this->logger = $logger;
    $this->currentUser = $currentUser;
    $this->exportService = $exportService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.user_export_plugin'),
      $container->get('logger.factory')->get('action'),
      $container->get('current_user'),
      $container->get('social_user_export.user_export')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    // Delegate to the VBO-agnostic export service.
    // The service handles CSV creation, writing, and finalization.
    // Plugin definitions are retrieved lazily when needed (not in constructor),
    // avoiding expensive plugin discovery during container build.
    return $this->exportService->processVboBatch(
      $entities,
      $this->exportService->getPluginDefinitions(),
      $this->context,
      [$this, 'getPluginConfiguration']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    assert($object instanceof UserInterface, 'Access check should only be called with UserInterface objects.');
    // @todo Check for export access instead.
    return $object->access('view', $account, $return_as_object);
  }

  /**
   * Gets export plugin's configuration.
   *
   * @param int $plugin_id
   *   The plugin ID.
   * @param int $entity_id
   *   The position of an entity in the entities list.
   *
   * @return array
   *   An array of export plugin's configuration.
   */
  public function getPluginConfiguration($plugin_id, $entity_id) {
    return [];
  }

}
