<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Bridge;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Database\StatementWrapperIterator;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class LogBridge {

  use EntityTrait;

  public function __construct(
    protected Connection $database,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('database'),
    );
  }

  /**
   * Clear out the watchdog table.
   */
  #[Command('watchdog-clear')]
  public function deleteAllLogMessages() : array {
    $this->database->truncate('watchdog')->execute();

    return ['status' => 'ok'];
  }

  /**
   * Get the messages stored in the watchdog table.
   *
   * We must query for this manually taking inspiration from the DbLogController
   * because there's no service that provides proper non-database access.
   *
   * @return array
   *   The result of the log message query.
   */
  #[Command('watchdog-list')]
  public function getLogMessages() : array {
    $query = $this->database->select('watchdog', 'w')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields('w', [
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ]);
    $query->leftJoin('users_field_data', 'ufd', '[w].[uid] = [ufd].[uid]');

    return ['messages' => $query->execute()->fetchAll()];
  }

}
