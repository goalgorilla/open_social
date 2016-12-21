<?php

namespace Drupal\social_user_export;

use \Drupal\user\UserInterface;
use \League\Csv\Writer;
use \Drupal\Core\Link;
use \Drupal\user\Entity\User;

class ExportUser {

  /**
   * Callback of one operation.
   */
  public static function exportUserOperation(UserInterface $entity, &$context) {
    if (empty($context['results']['file_path'])) {
      $context['results']['file_path'] = self::getFilePath();
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
        t('Registration date'),
        t('Status'),
        t('Roles'),
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
    if ($time = $entity->getLastLoginTime()) {
      $last_login = \Drupal::service('date.formatter')->format($time, 'custom', 'Y/m/d - H:i');
    }
    else {
      $last_login = t('never');
    }

    // Add row.
    $csv->insertOne([
      $entity->id(),
      $entity->uuid(),
      $entity->getEmail(),
      $last_login,
      \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'custom', 'Y/m/d - H:i'),
      !empty($status[0]['value']) ? t('Active') : t('Blocked'),
      implode(', ', $roles),
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
      $link = Link::createFromRoute(t('Download file'), 'social_user_export.export_user_download', [
        'name' => basename($results['file_path']),
      ]);

      drupal_set_message(t('Export is complete. @link', [
        '@link' => $link->toString(),
      ]));
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
  public static function getFilePath() {
    $filename = 'export-users-' . microtime(true) . '.csv';
    $file_path = file_directory_temp() . '/' . $filename;

    return $file_path;
  }

}
