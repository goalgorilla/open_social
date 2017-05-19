<?php

namespace Drupal\social_user_export;

use Drupal\Core\Url;
use \Drupal\user\UserInterface;
use \League\Csv\Writer;
use \Drupal\Core\Link;
use \Drupal\user\Entity\User;
use \Drupal\Component\Utility\Random;

class ExportUser {

  /**
   * Callback of one operation.
   */
  public static function exportUserOperation(UserInterface $entity, &$context) {
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
   */
  public static function exportUsersAllOperation($conditions, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $query = \Drupal::database()
        ->select('users', 'u')
        ->condition('u.uid', 0, '<>');

      if ($conditions) {
        social_user_export_user_apply_filter($query, $conditions);
      }

      $context['sandbox']['max'] = $query
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    $query = \Drupal::database()
      ->select('users', 'u')
      ->fields('u', ['uid'])
      ->condition('u.uid', $context['sandbox']['current_id'], '>');

    if ($conditions) {
      social_user_export_user_apply_filter($query, $conditions);
    }

    $uid = $query
      ->orderBy('u.uid')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    $account = User::load($uid);

    if ($account) {
      self::exportUserOperation($account, $context);
      $context['sandbox']['current_id'] = $uid;
    }

    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Callback when batch is complete.
   */
  public static function finishedCallback($success, $results, $operations) {
    if ($success && !empty($results['file_path'])) {
      $data = @file_get_contents($results['file_path']);
      $name = basename($results['file_path']);
      $path = 'private://csv';

      if (file_prepare_directory($path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS) && ($file = file_save_data($data, $path . '/' . $name))) {
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
      drupal_set_message('An error occurred', 'error');
    }
  }

  /**
   * Returns unique file path.
   *
   * @return string
   */
  public static function getFileTemporaryPath() {
    $hash = md5(microtime(TRUE));
    $filename = 'export-users-' . substr($hash, 20, 12) . '.csv';
    $file_path = file_directory_temp() . '/' . $filename;

    return $file_path;
  }

}
