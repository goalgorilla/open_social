<?php

namespace Drupal\social_user_export;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\user\UserInterface;
use League\Csv\Writer;
use Drupal\Core\Link;

/**
 * Class ExportUser.
 *
 * @package Drupal\social_user_export
 */
class ExportUser extends ContentEntityBase {

  /**
   * Callback of one operation.
   *
   * @param \Drupal\user\UserInterface $entity
   *   UserInterface entity.
   * @param array $context
   *   Context of the operation.
   */
  public static function exportUserOperation(UserInterface $entity, array &$context) {

    $type = \Drupal::service('plugin.manager.user_export_plugin');
    $plugin_definitions = $type->getDefinitions();

    // Check if headers exists.
    if (empty($context['results']['headers'])) {
      $headers = [];
      /** @var \Drupal\social_user_export\Plugin\UserExportPluginBase $instance */
      foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
        $instance = $type->createInstance($plugin_id);
        $headers[] = $instance->getHeader();
      }
      $context['results']['headers'] = $headers;
    }

    // Create the file if applicable.
    if (empty($context['results']['file_path'])) {
      $context['results']['file_path'] = self::getFileTemporaryPath();
      $csv = Writer::createFromPath($context['results']['file_path'], 'w');
      $csv->setDelimiter(',');
      $csv->setEnclosure('"');
      $csv->setEscape('\\');

      $csv->insertOne($context['results']['headers']);
    }
    else {
      $csv = Writer::createFromPath($context['results']['file_path'], 'a');
    }

    // Add formatter.
    $csv->addFormatter([new CsvEncoder(), 'formatRow']);

    $row = [];
    /** @var \Drupal\social_user_export\Plugin\UserExportPluginBase $instance */
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $instance = $type->createInstance($plugin_id);
      $row[] = $instance->getValue($entity);
    }
    $csv->insertOne($row);

    $context['message'] = t('Exporting: @name', [
      '@name' => $entity->getAccountName(),
    ]);
  }

  /**
   * Callback of massive operations.
   *
   * @param array $conditions
   *   Conditions of the operation.
   * @param array $context
   *   Context of the operation.
   */
  public static function exportUsersAllOperation(array $conditions, array &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;

      // Get max uid.
      $view = _social_user_export_get_view($conditions);
      $context['sandbox']['max'] = $view->total_rows;
    }

    $view = _social_user_export_get_view($conditions, FALSE);
    $view->initQuery();
    $view->query->orderby = [
      [
        'field' => 'users_field_data.uid',
        'direction' => 'ASC',
      ],
    ];
    $view->setOffset($context['sandbox']['progress']);
    $view->setItemsPerPage(1);
    $view->preExecute();
    $view->execute();

    if (empty($view->result[0])) {
      $context['finished'] = 1;
      return;
    }

    $account = $view->result[0]->_entity;

    if ($account) {
      self::exportUserOperation($account, $context);
    }

    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Callback when batch is complete.
   *
   * @param bool $success
   *   Boolean to indicate success of the batch.
   * @param array $results
   *   The results.
   * @param array $operations
   *   Operations that the batch performed.
   */
  public static function finishedCallback($success, array $results, array $operations) {
    if ($success && !empty($results['file_path'])) {
      $data = @file_get_contents($results['file_path']);
      $name = basename($results['file_path']);
      $path = 'private://csv';

      if (file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS) && (file_save_data($data, $path . '/' . $name))) {
        $url = Url::fromUri(file_create_url($path . '/' . $name));
        $link = Link::fromTextAndUrl(t('Download file'), $url);

        drupal_set_message(t('Export is complete. @link', [
          '@link' => $link->toString(),
        ]));
      }
      else {
        drupal_set_message(t('When saving the file an error occurred'), 'error');
      }
    }
    else {
      drupal_set_message(t('An error occurred', 'error'));
    }
  }

  /**
   * Returns unique file path.
   *
   * @return string
   *   The path to the file.
   */
  public static function getFileTemporaryPath() {
    $hash = md5(microtime(TRUE));
    $filename = 'export-users-' . substr($hash, 20, 12) . '.csv';
    $file_path = file_directory_temp() . '/' . $filename;

    return $file_path;
  }

}
