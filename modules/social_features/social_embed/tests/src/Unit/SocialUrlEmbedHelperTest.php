<?php

namespace Drupal\Tests\social_embed\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_embed\Service\SocialEmbedHelper;
use Drupal\social_embed\SocialUrlEmbedHelper;
use Drupal\Tests\UnitTestCase;
use Drupal\url_embed\UrlEmbedInterface;
use Embed\EmbedCode;
use Embed\Extractor;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\social_embed\SocialUrlEmbedHelper
 * @group social_embed
 */
class SocialUrlEmbedHelperTest extends UnitTestCase {

  /**
   * The URL embed service the helper fetches through.
   */
  protected UrlEmbedInterface&MockObject $urlEmbed;

  /**
   * The cache backend the helper stores embed data in.
   */
  protected CacheBackendInterface&MockObject $cacheBackend;

  /**
   * The class under test.
   */
  protected SocialUrlEmbedHelper $helper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->urlEmbed = $this->createMock(UrlEmbedInterface::class);
    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1000);

    // The real allow list is what is under test here, so this is not a mock.
    $embed_helper = new SocialEmbedHelper(
      $this->createMock(UuidInterface::class),
      $this->createMock(AccountProxyInterface::class),
      $this->createMock(Renderer::class),
      $this->createMock(ModuleHandlerInterface::class),
    );

    $this->helper = new SocialUrlEmbedHelper(
      $this->urlEmbed,
      $this->cacheBackend,
      $time,
      $embed_helper,
    );
  }

  /**
   * Tests that a URL we do not embed is never requested.
   *
   * The allow list lives here so that a caller can not forget it, which is
   * what makes this the assertion that matters: no request is made at all.
   *
   * @dataProvider providerUrlsWeDoNotEmbed
   * @covers ::getUrlInfo
   */
  public function testUrlWeDoNotEmbedIsNotFetched(string $url): void {
    // The cache is not consulted either. A refused URL must not be servable
    // from a cache entry, which pins the check to the top of the method.
    $this->cacheBackend->expects($this->never())->method('get');
    $this->cacheBackend->expects($this->never())->method('set');
    $this->urlEmbed->expects($this->never())->method('getEmbed');

    $this->assertNull(
      $this->helper->getUrlInfo($url),
      "Expected $url to be refused without being fetched."
    );
  }

  /**
   * URLs that must never reach a request.
   *
   * Each one contains a supported provider somewhere in the URL, which is what
   * made a caller without an allow list check fetch it.
   *
   * @return array<string, array<int, string>>
   *   The test cases, keyed by a description of the URL.
   */
  public static function providerUrlsWeDoNotEmbed(): array {
    return [
      'cloud metadata address' => ['http://169.254.169.254/youtu.be/x'],
      'loopback address' => ['http://127.0.0.1/youtube.com/watch?v=1'],
      'localhost with service port' => ['http://localhost:6379/youtu.be/x'],
      'internal hostname' => ['http://internal-admin/vimeo.com/123'],
      'provider as credentials' => ['http://youtube.com@169.254.169.254/watch?v=1'],
      'provider as subdomain' => ['https://youtube.com.evil.com/watch?v=1'],
      'provider in query string' => ['http://evil.com/?redir=youtube.com/watch?v=1'],
      'provider on a non default port' => ['https://youtube.com:6379/watch?v=1'],
      'file scheme' => ['file:///etc/passwd'],
      'unsupported host' => ['https://example.com/watch?v=1'],
      'supported host without embeddable path' => ['https://youtube.com/account'],
      'empty string' => [''],
    ];
  }

  /**
   * Tests that a URL of a supported provider is fetched and cached.
   *
   * @covers ::getUrlInfo
   */
  public function testSupportedUrlIsFetchedAndCached(): void {
    $url = 'https://www.youtube.com/watch?v=ojafuCcUZzU';
    $code = new EmbedCode('<iframe src="https://www.youtube.com/embed/ojafuCcUZzU"></iframe>');

    $extractor = $this->createMock(Extractor::class);
    $extractor->method('__get')->willReturnMap([
      ['code', $code],
      ['providerName', 'YouTube'],
      ['title', 'Some video'],
    ]);

    $this->cacheBackend->method('get')->willReturn(FALSE);
    $this->urlEmbed->expects($this->once())
      ->method('getEmbed')
      ->with($url)
      ->willReturn($extractor);

    $expected = [
      'code' => $code,
      'providerName' => 'YouTube',
      'title' => 'Some video',
    ];

    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with('social_embed_url:' . $url, $expected, 1000 + 3600);

    $this->assertSame($expected, $this->helper->getUrlInfo($url));
  }

  /**
   * Tests that cached embed data is returned without a new request.
   *
   * @covers ::getUrlInfo
   */
  public function testCachedDataIsReturnedWithoutFetching(): void {
    $url = 'https://www.youtube.com/watch?v=ojafuCcUZzU';
    $cached = [
      'code' => '<iframe src="https://www.youtube.com/embed/ojafuCcUZzU"></iframe>',
      'providerName' => 'YouTube',
      'title' => 'Some video',
    ];

    $cache = new \stdClass();
    $cache->data = $cached;

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('social_embed_url:' . $url)
      ->willReturn($cache);
    $this->urlEmbed->expects($this->never())->method('getEmbed');

    $this->assertSame($cached, $this->helper->getUrlInfo($url));
  }

}
