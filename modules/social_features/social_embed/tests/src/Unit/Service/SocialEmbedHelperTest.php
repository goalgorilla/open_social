<?php

namespace Drupal\Tests\social_embed\Unit\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_embed\Service\SocialEmbedHelper;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\social_embed\Service\SocialEmbedHelper
 * @group social_embed
 */
class SocialEmbedHelperTest extends UnitTestCase {

  /**
   * The class under test.
   */
  protected SocialEmbedHelper $helper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->helper = new SocialEmbedHelper(
      $this->createMock(UuidInterface::class),
      $this->createMock(AccountProxyInterface::class),
      $this->createMock(Renderer::class),
      $this->createMock(ModuleHandlerInterface::class),
    );
  }

  /**
   * Tests that URLs of supported providers are accepted.
   *
   * @dataProvider providerWhitelistedUrls
   * @covers ::isWhitelisted
   */
  public function testWhitelistedUrls(string $url): void {
    $this->assertTrue(
      $this->helper->isWhitelisted($url),
      "Expected $url to be whitelisted."
    );
  }

  /**
   * URLs that belong to a supported provider.
   *
   * @return array<string, array<int, string>>
   *   The test cases, keyed by a description of the URL.
   */
  public static function providerWhitelistedUrls(): array {
    return [
      'youtube with www' => ['https://www.youtube.com/watch?v=ojafuCcUZzU'],
      'youtube without www' => ['https://youtube.com/watch?v=ojafuCcUZzU'],
      'youtube over http' => ['http://youtube.com/watch?v=ojafuCcUZzU'],
      'youtube without scheme' => ['youtube.com/watch?v=ojafuCcUZzU'],
      'youtube short link' => ['https://youtu.be/ojafuCcUZzU'],
      'uppercase host' => ['https://WWW.YouTube.com/watch?v=ojafuCcUZzU'],
      // Providers serve content from more than one host.
      'youtube mobile sub domain' => ['https://m.youtube.com/watch?v=ojafuCcUZzU'],
      'youtube music sub domain' => ['https://music.youtube.com/watch?v=ojafuCcUZzU'],
      'vimeo player sub domain' => ['https://player.vimeo.com/video/123456789'],
      'facebook web sub domain' => ['https://web.facebook.com/opensocial/videos/123'],
      'vimeo' => ['https://vimeo.com/123456789'],
      'spotify track' => ['https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC'],
      'twitter status' => ['https://twitter.com/opensocial/status/123456789'],
      'x status' => ['https://x.com/opensocial/status/123456789'],
      'ted talk' => ['https://www.ted.com/talks/some_talk'],
      'flickr photo' => ['https://flickr.com/photos/someone/123456789'],
      'instagram post' => ['https://instagram.com/p/abc123'],
      // An explicit default port is the same request.
      'explicit https port' => ['https://youtube.com:443/watch?v=ojafuCcUZzU'],
      'explicit http port' => ['http://youtube.com:80/watch?v=ojafuCcUZzU'],
    ];
  }

  /**
   * Tests that URLs outside the supported providers are refused.
   *
   * Every internal case below still contains a provider pattern somewhere in
   * the URL, which is what made them pass before the host was resolved.
   *
   * @dataProvider providerNonWhitelistedUrls
   * @covers ::isWhitelisted
   */
  public function testNonWhitelistedUrls(string $url): void {
    $this->assertFalse(
      $this->helper->isWhitelisted($url),
      "Expected $url to be refused."
    );
  }

  /**
   * URLs that must never be fetched.
   *
   * @return array<string, array<int, string>>
   *   The test cases, keyed by a description of the URL.
   */
  public static function providerNonWhitelistedUrls(): array {
    return [
      // Internal addresses.
      'cloud metadata address' => ['http://169.254.169.254/youtu.be/x'],
      'localhost with port' => ['http://localhost:6379/youtu.be/x'],
      'loopback address' => ['http://127.0.0.1/youtube.com/watch?v=1'],
      'ipv6 loopback' => ['http://[::1]/youtu.be/x'],
      'private range' => ['http://10.0.0.5/open.spotify.com/track/x'],
      'internal hostname' => ['http://internal-admin/vimeo.com/123'],
      // Hosts made to look like a provider.
      'provider as credentials' => ['http://youtube.com@169.254.169.254/watch?v=1'],
      'provider as subdomain' => ['https://youtube.com.evil.com/watch?v=1'],
      'provider in query string' => ['http://evil.com/?redir=youtube.com/watch?v=1'],
      'provider in path' => ['http://evil.com/youtube.com/watch?v=1'],
      'protocol relative' => ['//evil.com/youtu.be/x'],
      'host ending in provider name' => ['https://notyoutube.com/watch?v=1'],
      // The port reaches the request without being matched on.
      'provider on a non default port' => ['https://youtube.com:22/watch?v=1'],
      'provider on a service port' => ['https://youtube.com:6379/watch?v=1'],
      'mismatched default port' => ['https://youtube.com:80/watch?v=1'],
      // Schemes we never embed.
      'file scheme' => ['file:///etc/passwd'],
      'gopher scheme' => ['gopher://127.0.0.1:6379/_INFO'],
      // Unsupported hosts and malformed input.
      'unsupported host' => ['https://example.com/watch?v=1'],
      'empty string' => [''],
    ];
  }

  /**
   * Tests that a supported host still needs an embeddable path.
   *
   * @dataProvider providerSupportedHostWithoutEmbeddablePath
   * @covers ::isWhitelisted
   */
  public function testWhitelistedHostWithoutEmbeddablePath(string $url): void {
    $this->assertFalse(
      $this->helper->isWhitelisted($url),
      "Expected $url to be refused, a supported host still needs an embeddable path."
    );
  }

  /**
   * URLs on a supported host that do not point at embeddable content.
   *
   * @return array<string, array<int, string>>
   *   The test cases, keyed by a description of the URL.
   */
  public static function providerSupportedHostWithoutEmbeddablePath(): array {
    return [
      'account page' => ['https://youtube.com/account'],
      'front page' => ['https://youtube.com/'],
      // Provider name in the query string, not the path.
      'other provider in query' => ['https://youtube.com/?next=vimeo.com/123'],
    ];
  }

  /**
   * Tests which provider a host resolves to.
   *
   * @dataProvider providerProviderHosts
   * @covers ::getProviderHost
   */
  public function testGetProviderHost(string $host, ?string $expected): void {
    $this->assertSame($expected, $this->helper->getProviderHost($host));
  }

  /**
   * Provider resolution cases.
   *
   * @return array<string, array<int, string|null>>
   *   The test cases, keyed by a description of the host.
   */
  public static function providerProviderHosts(): array {
    return [
      'exact match' => ['youtube.com', 'youtube.com'],
      'sub domain' => ['m.youtube.com', 'youtube.com'],
      'nested sub domain' => ['a.b.youtube.com', 'youtube.com'],
      'sub domain of a sub domain provider' => ['open.spotify.com', 'open.spotify.com'],
      'suffix without dot boundary' => ['notyoutube.com', NULL],
      'provider as a sub domain of something else' => ['youtube.com.example.com', NULL],
      'unrelated host' => ['example.com', NULL],
      'ip address' => ['169.254.169.254', NULL],
    ];
  }

  /**
   * Tests the host resolution used by the allow list.
   *
   * @dataProvider providerUrlHosts
   * @covers ::getUrlHost
   */
  public function testGetUrlHost(string $url, ?string $expected): void {
    $this->assertSame($expected, $this->helper->getUrlHost($url));
  }

  /**
   * Host resolution cases.
   *
   * @return array<string, array<int, string|null>>
   *   The test cases, keyed by a description of the URL.
   */
  public static function providerUrlHosts(): array {
    return [
      'strips www' => ['https://www.youtube.com/watch?v=1', 'youtube.com'],
      'lower cases host' => ['https://YouTube.COM/watch?v=1', 'youtube.com'],
      'keeps sub domain' => ['https://open.spotify.com/track/1', 'open.spotify.com'],
      'assumes https' => ['youtube.com/watch?v=1', 'youtube.com'],
      'strips trailing dot' => ['https://youtube.com./watch?v=1', 'youtube.com'],
      'keeps default port' => ['https://youtube.com:443/watch?v=1', 'youtube.com'],
      'refuses non default port' => ['http://localhost:6379/x', NULL],
      'refuses credentials' => ['http://youtube.com@127.0.0.1/x', NULL],
      'refuses file scheme' => ['file:///etc/passwd', NULL],
      'refuses empty string' => ['', NULL],
    ];
  }

}
