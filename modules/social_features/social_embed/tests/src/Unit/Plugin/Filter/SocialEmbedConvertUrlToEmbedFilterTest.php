<?php

namespace Drupal\Tests\social_embed\Unit\Plugin\Filter;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_embed\Plugin\Filter\SocialEmbedConvertUrlToEmbedFilter;
use Drupal\social_embed\Service\SocialEmbedHelper;
use Drupal\social_embed\SocialUrlEmbedHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\social_embed\Plugin\Filter\SocialEmbedConvertUrlToEmbedFilter
 * @group social_embed
 */
class SocialEmbedConvertUrlToEmbedFilterTest extends UnitTestCase {

  /**
   * The real pattern list, which is what is under test.
   */
  protected SocialEmbedHelper $embedHelper;

  /**
   * The URL lookup, configured per test case.
   */
  protected SocialUrlEmbedHelper&MockObject $urlEmbedHelper;

  /**
   * The class under test.
   */
  protected SocialEmbedConvertUrlToEmbedFilter $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->method('isAnonymous')->willReturn(TRUE);

    $this->embedHelper = new SocialEmbedHelper(
      $this->createMock(UuidInterface::class),
      $current_user,
      $this->createMock(Renderer::class),
      $this->createMock(ModuleHandlerInterface::class),
    );

    $this->urlEmbedHelper = $this->createMock(SocialUrlEmbedHelper::class);

    $container = new ContainerBuilder();
    $container->set('social_embed.helper_service', $this->embedHelper);
    $container->set('social_embed.url_embed_helper', $this->urlEmbedHelper);
    $container->set('current_user', $current_user);
    $container->set('config.factory', $this->getConfigFactoryStub([
      'social_embed.settings' => ['embed_consent_settings_an' => FALSE],
    ]));
    \Drupal::setContainer($container);

    $this->filter = new SocialEmbedConvertUrlToEmbedFilter(['settings' => []], 'social_embed_convert_url', ['provider' => 'social_embed'], $this->embedHelper);
  }

  /**
   * Tests that a supported URL is replaced by one embed and nothing else.
   *
   * @dataProvider providerUrlTexts
   * @covers ::convertUrls
   * @covers ::socialUrlToEmbed
   */
  public function testConvertUrls(string $text, string $expected, string $lookup_url): void {
    // No embed code means no consent placeholder: the URL becomes an embed.
    $this->urlEmbedHelper->expects($this->once())
      ->method('getUrlInfo')
      ->with($lookup_url)
      ->willReturn(['code' => '', 'providerName' => 'Vimeo', 'title' => '']);

    $this->assertSame($expected, $this->filter->convertUrls($text, $this->embedHelper->getCombinedPatterns()));
  }

  /**
   * Provides CKEditor texts, their expected markup and the looked-up URL.
   *
   * The lookup gets the decoded URL, as the render filter does, so both share
   * one cache entry.
   *
   * @return array<string, array{string, string, string}>
   *   The test cases, keyed by a description of the text.
   */
  public static function providerUrlTexts(): array {
    return [
      'vimeo share link keeps its query inside the embed' => [
        '<p>https://vimeo.com/1222036163/cdcddad642?share=copy&amp;fl=sv&amp;fe=ci</p>',
        '<p><drupal-url data-embed-url="https://vimeo.com/1222036163/cdcddad642?share=copy&amp;fl=sv&amp;fe=ci"></drupal-url></p>',
        'https://vimeo.com/1222036163/cdcddad642?share=copy&fl=sv&fe=ci',
      ],
      'vimeo id only link' => [
        '<p>https://vimeo.com/76979871</p>',
        '<p><drupal-url data-embed-url="https://vimeo.com/76979871"></drupal-url></p>',
        'https://vimeo.com/76979871',
      ],
    ];
  }

}
