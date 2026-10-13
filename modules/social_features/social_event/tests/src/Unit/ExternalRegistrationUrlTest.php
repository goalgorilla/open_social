<?php

namespace Drupal\Tests\social_event\Unit;

use Drupal\social_event\ExternalRegistrationUrl;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\social_event\ExternalRegistrationUrl
 */
class ExternalRegistrationUrlTest extends UnitTestCase {

  /**
   * @covers ::isValid
   *
   * @dataProvider providerUrls
   */
  public function testIsValid(string $url, bool $expected): void {
    $this->assertSame($expected, ExternalRegistrationUrl::isValid($url));
  }

  /**
   * URL cases for external registration.
   *
   * @return array<string, array{0: string, 1: bool}>
   *   The URL and whether it is valid.
   */
  public static function providerUrls(): array {
    return [
      'https luma event' => ['https://lu.ma/govtech-test', TRUE],
      'http url' => ['http://example.com/register', TRUE],
      'trimmed https url' => ['  https://example.com/register  ', TRUE],
      'empty' => ['', FALSE],
      'whitespace' => ['   ', FALSE],
      'missing scheme' => ['example.com/register', FALSE],
      'internal path' => ['/node/1', FALSE],
      'javascript' => ['javascript:alert(1)', FALSE],
      'spaces in url' => ['https://example.com/my event', FALSE],
    ];
  }

}
