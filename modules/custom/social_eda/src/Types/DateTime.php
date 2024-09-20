<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Type class for DateTime data.
 */
class DateTime {

  /**
   * Constructs the DateTime type.
   *
   * @param string $datetime
   *   The datetime.
   */
  public function __construct(
    public readonly string $datetime,
  ) {}

  /**
   * Get formatted DateTime output.
   *
   * @param int $timestamp
   *   The timestamp.
   *
   * @return self
   *   The DateTime data object.
   */
  public static function fromTimestamp(int $timestamp): self {
    $datetime = DrupalDateTime::createFromTimestamp($timestamp)->format(
      DateTimeItemInterface::DATETIME_STORAGE_FORMAT
    );

    return new self($datetime);
  }

  /**
   * Get the datetime string.
   *
   * @return string
   *   The datetime string.
   */
  public function toString(): string {
    return $this->datetime;
  }

  /**
   * Get the ImmutableDateTime output.
   *
   * @return \DateTimeImmutable
   *   The DateTimeImmutable.
   *
   * @throws \Exception
   */
  public function toImmutableDateTime(): \DateTimeImmutable {
    return new \DateTimeImmutable($this->datetime);
  }

}
