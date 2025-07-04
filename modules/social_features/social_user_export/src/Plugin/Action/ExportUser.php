<?php

namespace Drupal\social_user_export\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileRepository;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use League\Csv\Writer;
use Drupal\Core\Link;
use Drupal\social_user_export\Plugin\UserExportPluginManager;

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
   * Starting characters for spreadsheet formulas.
   */
  private const FORMULAS_START_CHARACTERS = ['=', '-', '+', '@', "\t", "\r"];

  /**
   * The User export plugin manager.
   *
   * @var \Drupal\social_user_export\Plugin\UserExportPluginManager
   */
  protected UserExportPluginManager $userExportPlugin;

  /**
   * User export plugin definitions.
   *
   * @var array
   */
  protected array $pluginDefinitions;

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
   * The user export plugin config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * File URL Generator services.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected FileUrlGenerator $fileUrlGenerator;

  /**
   * File repository services.
   *
   * @var \Drupal\file\FileRepository
   */
  protected FileRepository $fileRepository;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory for the export plugin access.
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file url generator service.
   * @param \Drupal\file\FileRepository $file_repository
   *   The file repository service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserExportPluginManager $userExportPlugin,
    LoggerInterface $logger,
    AccountProxyInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    FileUrlGenerator $file_url_generator,
    FileRepository $file_repository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userExportPlugin = $userExportPlugin;
    $this->logger = $logger;
    $this->currentUser = $currentUser;
    $this->config = $configFactory->get('social_user_export.settings');

    // Get the definitions, check for access and and sort them by weight.
    $definitions = $this->userExportPlugin->getDefinitions();
    $this->pluginDefinitions = $this->pluginAccess($definitions);
    usort($this->pluginDefinitions, [$this, 'sortDefinitions']);
    $this->fileUrlGenerator = $file_url_generator;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.user_export_plugin'),
      $container->get('logger.factory')->get('action'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('file_url_generator'),
      $container->get('file.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {

    // Check if headers exists.
    if (empty($this->context['sandbox']['results']['headers'])) {
      $headers = [];
      /** @var \Drupal\social_user_export\Plugin\UserExportPluginBase $instance */
      foreach ($this->pluginDefinitions as $plugin) {
        $instance = $this->userExportPlugin->createInstance($plugin['id']);
        $headers[] = $instance->getHeader();
      }
      $this->context['sandbox']['results']['headers'] = $headers;
    }

    // Create the file if applicable.
    if (empty($this->context['sandbox']['results']['file_path'])) {
      // Store only the name relative to the output directory. On platforms such
      // as Pantheon, different batch ticks can happen on different webheads.
      // This can cause the file mount path to change, thus changing where on
      // disk the tmp folder is actually located.
      $this->context['sandbox']['results']['file_path'] = $this->generateFilePath();
      $file_path = $this->getBaseOutputDirectory() . DIRECTORY_SEPARATOR . $this->context['sandbox']['results']['file_path'];

      $csv = Writer::createFromPath($file_path, 'w');
      $csv->setDelimiter(',');
      $csv->setEnclosure('"');
      $csv->setEscape('\\');

      $csv->insertOne($this->context['sandbox']['results']['headers']);
    }
    else {
      $file_path = $this->getBaseOutputDirectory() . DIRECTORY_SEPARATOR . $this->context['sandbox']['results']['file_path'];
      $csv = Writer::createFromPath($file_path, 'a');
    }

    // Add formatter.
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);

    // Now add the entities to export.
    foreach ($entities as $entity_id => $entity) {
      $row = [];
      /** @var \Drupal\social_user_export\Plugin\UserExportPluginBase $instance */
      foreach ($this->pluginDefinitions as $plugin) {
        $configuration = $this->getPluginConfiguration($plugin['id'], $entity_id);
        $instance = $this->userExportPlugin->createInstance($plugin['id'], $configuration);
        $this->writeRow($row, $instance->getValue($entity));
      }
      $csv->insertOne($row);
    }

    if (($this->context['sandbox']['current_batch'] * $this->context['sandbox']['batch_size']) >= $this->context['sandbox']['total']) {
      $data = @file_get_contents($file_path);
      if (is_string($data)) {
        $name = basename($this->context['sandbox']['results']['file_path']);
        $path = 'private://csv';

        if (\Drupal::service('file_system')->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
          $this->fileRepository->writeData($data, $path . '/' . $name);
          $url = Url::fromUri($this->fileUrlGenerator->generateAbsoluteString($path . '/' . $name));
          $link = Link::fromTextAndUrl($this->t('Download file'), $url);

          $this->messenger()->addMessage($this->t('Export is complete. @link', [
            '@link' => $link->toString(),
          ]));
        }
        else {
          $this->messenger()->addMessage($this->t('Could not save the export file.'), 'error');
          $this->logger->error('Could not save the export file on: %name.', ['%name' => $name]);
        }
      }
    }

    return [];
  }

  /**
   * Write values to a CSV row.
   *
   * This also escapes strings starting with a formula character.
   *
   * @param array $row
   *   The row to inject the value into.
   * @param string $value
   *   The value to insert.
   */
  protected function writeRow(array &$row, string $value): void {
    // The single quote ' is recommended to prefix formulas.
    if (\in_array(substr($value, 0, 1), self::FORMULAS_START_CHARACTERS, TRUE)) {
      $row[] = "'" . $value;
    }
    else {
      $row[] = $value;
    }
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
    /** @var \Drupal\user\UserInterface $object */
    // @todo Check for export access instead.
    return $object->access('view', $account, $return_as_object);
  }

  /**
   * Returns the directory that forms the base for this exports file output.
   *
   * This method wraps file_directory_temp() to give inheriting classes the
   * ability to use a different file system than the temporary file system.
   * This was previously possible but was changed in #3075818.
   *
   * @return string
   *   The path to the Drupal directory that should be used for this export.
   */
  protected function getBaseOutputDirectory() : string {
    return \Drupal::service('file_system')->getTempDirectory();
  }

  /**
   * Returns a unique file path for this export.
   *
   * The returned path is relative to getBaseOutputDirectory(). This allows it
   * to work on distributed systems where the temporary file path may change
   * in between batch ticks.
   *
   * To make sure the file can be downloaded, the path must be declared in the
   * download pattern of the social user export module.
   *
   * @see social_user_export_file_download()
   *
   * @return string
   *   The path to the file.
   */
  protected function generateFilePath() : string {
    return 'export-users-' . bin2hex(random_bytes(8)) . '.csv';
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

  /**
   * Check the access of export plugins based on config and permission.
   *
   * @param array $definitions
   *   The plugin definitions.
   *
   * @return array
   *   Returns only the plugins the user has access to.
   */
  protected function pluginAccess(array $definitions) :array {
    // When the user has access to administer users we know they may export all
    // the available data.
    if ($this->currentUser->hasPermission('administer users')) {
      return $definitions;
    }

    // Now we go through all the definitions and check if they should be removed
    // or not based upon the config set by the site manager.
    $allowed_plugins = $this->config->get('plugins');
    foreach ($definitions as $key => $definition) {
      if (!array_key_exists($definition['id'], $allowed_plugins) || empty($allowed_plugins[$definition['id']])) {
        unset($definitions[$key]);
      }
    }

    return $definitions;
  }

  /**
   * Order by weight.
   *
   * @param array $a
   *   First parameter.
   * @param array $b
   *   Second parameter.
   *
   * @return int
   *   The weight to be used for the usort function.
   */
  protected function sortDefinitions(array $a, array $b) :int {
    if (isset($a['weight'], $b['weight'])) {
      return $a['weight'] < $b['weight'] ? -1 : 1;
    }
    return 0;
  }

}
