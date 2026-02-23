<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Tests\Renderer;

use OpenSocial\RichTextJson\Renderer\UrlSanitizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for UrlSanitizer.
 */
#[CoversClass(UrlSanitizer::class)]
final class UrlSanitizerTest extends TestCase {

  /**
   * Tests that http URLs are allowed.
   */
  public function testAllowsHttpUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('http://example.com', $sanitizer->sanitize('http://example.com'));
  }

  /**
   * Tests that https URLs are allowed.
   */
  public function testAllowsHttpsUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('https://example.com', $sanitizer->sanitize('https://example.com'));
  }

  /**
   * Tests that mailto URLs are allowed.
   */
  public function testAllowsMailtoUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('mailto:test@example.com', $sanitizer->sanitize('mailto:test@example.com'));
  }

  /**
   * Tests that tel URLs are allowed.
   */
  public function testAllowsTelUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('tel:+1234567890', $sanitizer->sanitize('tel:+1234567890'));
  }

  /**
   * Tests that relative URLs are allowed.
   */
  public function testAllowsRelativeUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('/path/to/page', $sanitizer->sanitize('/path/to/page'));
    self::assertSame('path/to/page', $sanitizer->sanitize('path/to/page'));
    self::assertSame('../page', $sanitizer->sanitize('../page'));
  }

  /**
   * Tests that anchor-only URLs are allowed.
   */
  public function testAllowsAnchorOnlyUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('#section', $sanitizer->sanitize('#section'));
  }

  /**
   * Tests that javascript: URLs are blocked.
   */
  public function testBlocksJavascriptUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('', $sanitizer->sanitize('javascript:alert(1)'));
  }

  /**
   * Tests that javascript: URLs are blocked case-insensitively.
   */
  public function testBlocksJavascriptUrlsCaseInsensitive(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('', $sanitizer->sanitize('JavaScript:alert(1)'));
    self::assertSame('', $sanitizer->sanitize('JAVASCRIPT:alert(1)'));
    self::assertSame('', $sanitizer->sanitize('jAvAsCrIpT:alert(1)'));
  }

  /**
   * Tests that data: URLs are blocked.
   */
  public function testBlocksDataUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('', $sanitizer->sanitize('data:text/html,<script>alert(1)</script>'));
  }

  /**
   * Tests that vbscript: URLs are blocked.
   */
  public function testBlocksVbscriptUrls(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('', $sanitizer->sanitize('vbscript:msgbox(1)'));
  }

  /**
   * Tests that URLs with whitespace before scheme are blocked.
   */
  public function testBlocksUrlsWithLeadingWhitespace(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('', $sanitizer->sanitize('  javascript:alert(1)'));
    self::assertSame('', $sanitizer->sanitize("\tjavascript:alert(1)"));
    self::assertSame('', $sanitizer->sanitize("\njavascript:alert(1)"));
  }

  /**
   * Tests that encoded javascript: URLs are blocked.
   */
  public function testBlocksEncodedJavascriptUrls(): void {
    $sanitizer = new UrlSanitizer();
    // HTML entity encoded "javascript:" schemes.
    self::assertSame('', $sanitizer->sanitize('java&#115;cript:alert(1)'));
    self::assertSame('', $sanitizer->sanitize('java&#x73;cript:alert(1)'));
  }

  /**
   * Tests URL with query string and fragment.
   */
  public function testAllowsUrlsWithQueryAndFragment(): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame(
      'https://example.com/page?foo=bar#section',
      $sanitizer->sanitize('https://example.com/page?foo=bar#section'),
    );
  }

  /**
   * Data provider for dangerous URLs.
   *
   * @return array<string, array{string}>
   *   Test data.
   */
  public static function dangerousUrlsProvider(): array {
    return [
      'javascript lowercase' => ['javascript:alert(1)'],
      'javascript uppercase' => ['JAVASCRIPT:alert(1)'],
      'javascript mixed case' => ['JaVaScRiPt:alert(1)'],
      'data url' => ['data:text/html,test'],
      'vbscript' => ['vbscript:test'],
      'javascript with spaces' => ['  javascript:alert(1)'],
      'javascript with tab' => ["\tjavascript:alert(1)"],
      'javascript with newline' => ["\njavascript:alert(1)"],
    ];
  }

  /**
   * Tests that various dangerous URLs are blocked.
   *
   * @param string $url
   *   The dangerous URL.
   */
  #[DataProvider('dangerousUrlsProvider')]
  public function testBlocksDangerousUrls(string $url): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame('', $sanitizer->sanitize($url));
  }

  /**
   * Data provider for safe URLs.
   *
   * @return array<string, array{string}>
   *   Test data.
   */
  public static function safeUrlsProvider(): array {
    return [
      'https' => ['https://example.com'],
      'http' => ['http://example.com'],
      'mailto' => ['mailto:test@example.com'],
      'tel' => ['tel:+1234567890'],
      'relative path' => ['/path/to/page'],
      'relative no slash' => ['page.html'],
      'parent relative' => ['../page.html'],
      'anchor only' => ['#section'],
      'empty string' => [''],
    ];
  }

  /**
   * Tests that various safe URLs are allowed.
   *
   * @param string $url
   *   The safe URL.
   */
  #[DataProvider('safeUrlsProvider')]
  public function testAllowsSafeUrls(string $url): void {
    $sanitizer = new UrlSanitizer();
    self::assertSame($url, $sanitizer->sanitize($url));
  }

}
