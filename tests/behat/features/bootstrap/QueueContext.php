<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;

/**
 * Context for interacting with the job queues.
 */
class QueueContext implements Context {

  use DrushTrait;

  /**
   * Process the items in the queue.
   *
   * Calls drush queue:run and advanced:queue:process.
   *
   * @When I wait for the queue to be empty
   */
  public function iWaitForTheQueueToBeEmpty() : void {
    // We process the queues multiple times because the notification system
    // might move things from one queue to another. We stop if we have a cycle
    // where none of the queues had anything to process or in case we reach
    // 20 cycles, to ensure that we don't run forever.
    $max_cycles = 20;
    do {
      $has_processed_items = FALSE;
      foreach ($this->getQueues() as $queueName => $queue) {
        if ($queue['items'] === 0) {
          continue;
        }
        $has_processed_items = TRUE;
        $result = $this->drush(['queue:run', $queueName]);
        if ($result->exitCode !== 0) {
          throw new \RuntimeException("Failed to process queue using Drush: {$result->errorOutput}.");
        }
      }
    } while ($has_processed_items && --$max_cycles > 0);

    foreach ($this->getAdvancedQueues() as $queue) {
      $result = $this->drush(['advanced:queue:process', '--timeout=600', $queue['id']]);
      if ($result->exitCode !== 0) {
        throw new \RuntimeException("Failed to process advanced queue using Drush: {$result->errorOutput}.");
      }
    }
  }

  /**
   * Clears the items in the queue.
   *
   * Calls Drush queue:delete and advancedqueue:queue:process as the
   * advanced queue module doesn't have a way to empty it.
   *
   * @When I empty the queue
   */
  public function iEmptyTheQueue() : void {
    foreach ($this->getQueues() as $queueName => $queue) {
      $result = $this->drush(['queue:delete', $queueName]);
      if ($result->exitCode !== 0) {
        throw new \RuntimeException("Failed to clear queue using Drush: {$result->errorOutput}.");
      }
    }

    // The advanced queue doesn't have a way to empty it, so we simply process
    // the items that are there, which mirrors the previous direct-database
    // implementation that went before the drush version.
    foreach ($this->getAdvancedQueues() as $queue) {
      $result = $this->drush(['advanced:queue:process', '--timeout=600', $queue['id']]);
      if ($result->exitCode !== 0) {
        throw new \RuntimeException("Failed to clear advanced queue using Drush: {$result->errorOutput}.");
      }
    }
  }

  /**
   * Get the list of queues that are available.
   *
   * @return array<string, array{queue: string, items: int, class: string}
   *   The list of queues as provided by Drush.
   */
  public function getQueues() : array {
    $result = $this->drush(['queue:list', '--format=json']);

    if ($result->exitCode !== 0) {
      throw new \RuntimeException("Failed to get list of queues using Drush: {$result->errorOutput}.");
    }

    return json_decode($result->output, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * Get the list of advanced queues that are available.
   *
   * @return list<array{id: string, label: string, jobs: array{queued: int, processing: int, success: int, failure: int}}>
   *   The list of advanced queues as provided by Drush.
   */
  public function getAdvancedQueues() : array {
    $result = $this->drush(['advanced:queue:list', '--format=json']);

    if ($result->exitCode !== 0) {
      throw new \RuntimeException("Failed to get list of advanced queues using Drush: {$result->errorOutput}.");
    }

    // Map a string like "Queued: 0 | Processing: 0 | Success: 0 | Failure: 0"
    // into ["queued" => 0, "processing" => 0, "success" => 0, "failure" => 0].
    return array_map(
      fn ($item) => [
        ...$item,
        "jobs" => array_reduce(
          array_map(
            fn ($job) => explode(": ", strtolower(trim($job))),
            explode("|", $item['jobs'])
          ),
          fn ($carry, $item) => array_merge($carry, [$item[0] => (int) $item[1]]),
          []
        ),
      ],
      json_decode($result->output, TRUE, 512, JSON_THROW_ON_ERROR)
    );
  }

}
