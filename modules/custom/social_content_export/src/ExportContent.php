<?php

namespace Drupal\social_content_export;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use League\Csv\Writer;

/**
 * Class ExportContent.
 *
 * @package Drupal\social_content_export
 */
class ExportContent extends PrivateTempStoreFactory {

  /**
   * Callback of one operation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   ContentInterface entity.
   * @param array $context
   *   Context of the operation.
   */
  public static function exportContentOperation(ContentEntityInterface $entity, array &$context) {
    $bundle = $entity->bundle();
    switch ($bundle) {
      case 'event':
        if (empty($context['results']['file_path'])) {
          $context['results']['file_path'] = self::getFileTemporaryPath();
          $csv = Writer::createFromPath($context['results']['file_path'], 'w');
          $csv->setDelimiter(',');
          $csv->setEnclosure('"');
          $csv->setEscape('\\');

          // Append header.
          $headers = [
            t('Event Name'),
            t('Location'),
            t('EventHost'),
            t('Event Organizer'),
            t("Enrolled Participants"),
          ];
          $csv->insertOne($headers);
        }
        else {
          $csv = Writer::createFromPath($context['results']['file_path'], 'a');
        }

        // Add formatter.
        $encoder = \Drupal::service('csv_serialization.encoder.csv');
        $csv->addFormatter([$encoder, 'formatRow']);

        // Add row.
        $csv->insertOne([
          social_content_export_event_title($entity),
          social_content_export_event_location($entity),
          social_content_export_event_host($entity),
          social_content_export_event_participant($entity),
          social_content_export_event_organizer($entity),
        ]);
        break;

      case 'topic':
        drupal_set_message('Not yet for topics');
        break;

      case 'article':
        drupal_set_message('Not yet for articles');
        break;
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
        drupal_set_message('When saving the file an error occurred', 'error');
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
   *   The path to the file.
   */
  public static function getFileTemporaryPath() {
    $hash = md5(microtime(TRUE));
    $filename = 'export-contents-' . substr($hash, 20, 12) . '.csv';
    $file_path = file_directory_temp() . '/' . $filename;

    return $file_path;
  }

}
