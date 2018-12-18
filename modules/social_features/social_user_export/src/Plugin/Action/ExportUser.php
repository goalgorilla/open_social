<?php

namespace Drupal\social_user_export\Plugin\Action;

use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use League\Csv\Writer;
use Drupal\Core\Link;
use Drupal\social_user_export\Plugin\UserExportPluginManager;

/**
 * Exports a user accounts to CSV.
 *
 * @Action(
 *   id = "social_user_export_user_action",
 *   label = @Translation("Export the selected users to CSV"),
 *   type = "user",
 *   confirm = TRUE
 * )
 */
class ExportUser extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {
  use MessengerTrait;

  /**
   * The User export plugin manager.
   *
   * @var \Drupal\social_user_export\Plugin\UserExportPluginManager
   */
  protected $userExportPlugin;

  /**
   * User export plugin definitions.
   *
   * @var array
   */
  protected $pluginDefinitions;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserExportPluginManager $userExportPlugin, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userExportPlugin = $userExportPlugin;
    $this->pluginDefinitions = $this->userExportPlugin->getDefinitions();
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.user_export_plugin'),
      $container->get('logger.factory')->get('action')
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
      foreach ($this->pluginDefinitions as $plugin_id => $plugin_definition) {
        $instance = $this->userExportPlugin->createInstance($plugin_id);
        $headers[] = $instance->getHeader();
      }
      $this->context['sandbox']['results']['headers'] = $headers;
    }

    // Create the file if applicable.
    if (empty($this->context['sandbox']['results']['file_path'])) {
      $this->context['sandbox']['results']['file_path'] = $this->getFileTemporaryPath();
      $csv = Writer::createFromPath($this->context['sandbox']['results']['file_path'], 'w');
      $csv->setDelimiter(',');
      $csv->setEnclosure('"');
      $csv->setEscape('\\');

      $csv->insertOne($this->context['sandbox']['results']['headers']);
    }
    else {
      $csv = Writer::createFromPath($this->context['sandbox']['results']['file_path'], 'a');
    }

    // Add formatter.
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);

    // Now add the entities to export.
    foreach ($entities as $entity) {
      $row = [];
      /** @var \Drupal\social_user_export\Plugin\UserExportPluginBase $instance */
      foreach ($this->pluginDefinitions as $plugin_id => $plugin_definition) {
        $instance = $this->userExportPlugin->createInstance($plugin_id);
        $row[] = $instance->getValue($entity);
      }
      $csv->insertOne($row);
    }

    if (($this->context['sandbox']['current_batch'] * $this->context['sandbox']['batch_size']) >= $this->context['sandbox']['total']) {
      $data = @file_get_contents($this->context['sandbox']['results']['file_path']);
      $name = basename($this->context['sandbox']['results']['file_path']);
      $path = 'private://csv';

      if (file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS) && (file_save_data($data, $path . '/' . $name))) {
        $url = Url::fromUri(file_create_url($path . '/' . $name));
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

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    // TODO Check for export access instead.
    return $object->access('view', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Returns unique file path.
   *
   * @return string
   *   The path to the file.
   */
  public function getFileTemporaryPath() {
    $hash = md5(microtime(TRUE));
    $filename = 'export-users-' . substr($hash, 20, 12) . '.csv';
    return file_directory_temp() . '/' . $filename;
  }

}
