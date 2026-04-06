<?php

declare(strict_types=1);

namespace Drupal\Tests\signed_file_upload\Kernel;

use Drupal\Component\Datetime\TimeInterface;

/**
 * Mutable request time for kernel tests (advance the clock between requests).
 */
final class MutableRequestTime implements TimeInterface {

  public function __construct(private int $requestTime) {}

  /**
   * Advance the time in seconds.
   *
   * @param int $seconds
   *   The amount of seconds to advance time by.
   */
  public function advance(int $seconds): void {
    $this->requestTime += $seconds;
  }

  /**
   * Set the request time to a specific timestamp.
   *
   * @param int $timestamp
   *   The new request time.
   */
  public function setRequestTime(int $timestamp): void {
    $this->requestTime = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->requestTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestMicroTime() {
    return (float) $this->requestTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return $this->requestTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentMicroTime() {
    return (float) $this->requestTime;
  }

}
