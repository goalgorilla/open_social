<?php

declare(strict_types=1);

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Component\Datetime\Time;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Allows controlling time during tests.
 */
class TestTimeService extends Time {

  /**
   * The current time in the test.
   */
  protected int $requestTime;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $requestStack) {
    parent::__construct($requestStack);
    $this->requestTime = time();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->requestTime;
  }

  /**
   * Advances the reported request time.
   *
   * @param int $seconds
   *   (optional) Number of seconds by which to advance the reported request
   *   time.
   *
   * @return $this
   */
  public function advanceTime($seconds = 1) {
    $this->requestTime += $seconds;
    return $this;
  }

}
