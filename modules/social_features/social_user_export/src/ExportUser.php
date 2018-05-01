<?php

namespace Drupal\social_user_export;

use Drupal\Core\Url;
use Drupal\user\UserInterface;
use League\Csv\Writer;
use Drupal\Core\Link;

/**
 * Class ExportUser.
 *
 * @package Drupal\social_user_export
 */
class ExportUser {

  /**
   * Callback of one operation.
   *
   * @param \Drupal\user\UserInterface $entity
   *   UserInterface entity.
   * @param array $context
   *   Context of the operation.
   */
  public static function exportUserOperation(UserInterface $entity, array &$context) {
    if (empty($context['results']['file_path'])) {
      $context['results']['file_path'] = self::getFileTemporaryPath();
      $csv = Writer::createFromPath($context['results']['file_path'], 'w');
      $csv->setDelimiter(',');
      $csv->setEnclosure('"');
      $csv->setEscape('\\');

      // Append header.
      $headers = [
        t('ID'),
        t('UUID'),
        t('Email'),
        t('Last login'),
        t('Last access'),
        t('Registration date'),
        t('Status'),
        t('Roles'),
        t('Posts created'),
        t('Comments created'),
        t('Topics created'),
        t('Events enrollments'),
        t('Events created'),
        t('Groups created'),
      ];
      $csv->insertOne($headers);
    }
    else {
      $csv = Writer::createFromPath($context['results']['file_path'], 'a');
    }

    // Add formatter.
    $encoder = \Drupal::service('csv_serialization.encoder.csv');
    $csv->addFormatter([$encoder, 'formatRow']);
    $roles = $entity->getRoles();
    $status = $entity->get('status')->getValue();

    // Format last login time.
    if ($last_login_time = $entity->getLastLoginTime()) {
      $last_login = \Drupal::service('date.formatter')->format($last_login_time, 'custom', 'Y/m/d - H:i');
    }
    else {
      $last_login = t('never');
    }

    // Format last access time.
    if ($last_access_time = $entity->getLastAccessedTime()) {
      $last_access = \Drupal::service('date.formatter')->format($last_access_time, 'custom', 'Y/m/d - H:i');
    }
    else {
      $last_access = t('never');
    }

    // Add row.
    $csv->insertOne([
      $entity->id(),
      $entity->uuid(),
      $entity->getEmail(),
      $last_login,
      $last_access,
      \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'custom', 'Y/m/d - H:i'),
      !empty($status[0]['value']) ? t('Active') : t('Blocked'),
      implode(', ', $roles),
      social_user_export_posts_count($entity),
      social_user_export_comments_count($entity),
      social_user_export_nodes_count($entity, 'topic'),
      social_user_export_events_enrollments_count($entity),
      social_user_export_nodes_count($entity, 'event'),
      social_user_export_groups_count($entity),
    ]);

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
