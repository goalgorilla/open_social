<?php

namespace Drupal\download_count\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Database\Database;

/**
 * @QueueWorker(
 *   id = "download_count",
 *   title = @Translation("Download Count Cache Processor"),
 *   cron = {"time" = 60}
 * )
 */
class DownloadCountCacheProcessor extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Logs a notice.
    $connection = Database::getConnection();
    $connection->merge('download_count_cache')
      ->key(array(
        'type' => $data->type,
        'id' => $data->id,
        'fid' => $data->fid,
        'date' => $data->date
      ))
      ->fields(array(
        'count' => $data->count,
      ))
      ->expression('count', 'count + :inc', array(':inc' => $data->count))
      ->execute();
  }
}
