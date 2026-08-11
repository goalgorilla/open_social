<?php

namespace Drupal\social\Behat\Loki;

/**
 * A single log line returned by a Loki query, with its metadata.
 */
final class LogEntry {

  /**
   * @param array<string, string> $metadata
   *   The entry's combined Loki stream labels and structured metadata (e.g.
   *   `severity_text`, `open_social_cms_log_channel`, `user_id`, ...).
   */
  public function __construct(
    public readonly \DateTimeImmutable $timestamp,
    public readonly string $message,
    public readonly array $metadata,
  ) {}

  /**
   * The Monolog channel the entry was logged under (e.g. "php", "user").
   *
   * This is the equivalent of dblog's `watchdog.type` column.
   */
  public function getChannel() : ?string {
    return $this->metadata['open_social_cms_log_channel'] ?? NULL;
  }

  /**
   * The OTel severity text (e.g. "NOTICE", "WARNING", "ERROR").
   *
   * This is the equivalent of dblog's `watchdog.severity` column, but as the
   * Monolog level name rather than an RfcLogLevel integer.
   */
  public function getSeverityText() : ?string {
    return $this->metadata['severity_text'] ?? NULL;
  }

  public static function fromStreamResult(array $stream, string $timestampNs, string $message) : self {
    $seconds = intdiv((int) $timestampNs, 1_000_000_000);
    $nanoRemainder = ((int) $timestampNs) % 1_000_000_000;
    $timestamp = (new \DateTimeImmutable("@$seconds"))
      ->setTimezone(new \DateTimeZone('UTC'))
      ->modify('+' . intdiv($nanoRemainder, 1000) . ' microseconds');

    return new self($timestamp, $message, $stream);
  }

}
