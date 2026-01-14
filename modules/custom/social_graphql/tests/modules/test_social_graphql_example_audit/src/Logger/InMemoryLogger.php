<?php

declare(strict_types=1);

namespace Drupal\test_social_graphql_example_audit\Logger;

use Psr\Log\AbstractLogger;

/**
 * A simple in memory logger.
 */
class InMemoryLogger extends AbstractLogger {

  /**
   * The log messages.
   *
   * @var array
   */
  private array $messages = [];

  /**
   * {@inheritdoc}
   */
  public function log(mixed $level, string|\Stringable $message, array $context = []): void {
    $this->messages[] = [
      'level' => $level,
      'message' => $message,
      'context' => $context,
    ];
  }

  /**
   * Get the number of messages logged.
   */
  public function getLogCount() : int {
    return count($this->messages);
  }

  /**
   * Gets the last log message.
   *
   * @return array|null
   *   The last message that was logged or NULL if there was none.
   *
   * @phpstan-return null|array{level: string, message: string, context: array}
   */
  public function getLastMessage() : ?array {
    return end($this->messages) ?: NULL;
  }

}
