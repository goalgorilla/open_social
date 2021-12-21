<?php

namespace Drupal\social_embed\Service;

use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\Component\Uuid\UuidInterface;

/**
 * Service class for Social Embed.
 */
class SocialEmbedHelper {

  /**
   * Uuid generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected UuidInterface $uuidGenerator;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Renderer services.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Constructor for SocialEmbedHelper.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user object.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer services.
   */
  public function __construct(UuidInterface $uuid_generator, AccountProxyInterface $current_user, Renderer $renderer) {
    $this->uuidGenerator = $uuid_generator;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
  }

  /**
   * Adds given cache tag and drupal ajax library.
   *
   * @param \Drupal\filter\FilterProcessResult $result
   *   FilterProcessResult object on which changes need to happen.
   * @param string $tag
   *   Tag to add.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The object itself.
   *
   * @see \Drupal\social_embed\Plugin\Filter\SocialEmbedConvertUrlToEmbedFilter
   * @see \Drupal\social_embed\Plugin\Filter\SocialEmbedUrlEmbedFilter
   */
  public function addDependencies(FilterProcessResult $result, string $tag): FilterProcessResult {
    // Add our custom tag so that we invalidate them when site manager
    // changes consent settings.
    // @see EmbedConsentForm
    $result->addCacheTags([$tag]);

    // Add user specific tag.
    $uid = $this->currentUser->id();
    $result->addCacheTags(["social_embed.filter.$uid"]);

    // We want to vary cache per user so the user settings can also be taken
    // into consent.
    $result->addCacheContexts(['user']);

    // We need this library to be attached as we are using 'use-ajax'
    // class in the show consent button markup.
    $result->addAttachments([
      'library' => [
        'core/drupal.ajax',
        'social_embed/consent-placeholder',
      ],
    ]);

    return $result;
  }

  /**
   * Checks if item is on the whitelist.
   *
   * @param string $text
   *   The item to check for.
   *
   * @return bool
   *   Return if the item is on the whitelist or not.
   */
  public function whiteList($text) {
    // Fetch allowed patterns.
    $patterns = $this->getPatterns();

    // Check if the URL provided is from a whitelisted site.
    foreach ($patterns as $pattern) {
      // Testing pattern.
      $testing_pattern = '/' . $pattern . '/';
      // Check if it matches.
      if (preg_match($testing_pattern, $text)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * A list of whitelisted patterns.
   *
   * @return array
   *   The list of patterns.
   */
  private function getPatterns() {
    return [
      'facebook.com\/(.*)\/videos\/(.*)',
      'facebook.com\/(.*)\/photos\/(.*)',
      'facebook.com\/(.*)\/posts\/(.*)',
      'flickr.com\/photos\/(.*)',
      'flic.kr\/p\/(.*)',
      'instagram.com\/p\/(.*)',
      'open.spotify.com\/track\/(.*)',
      'twitter.com\/(.*)\/status\/(.*)',
      'vimeo.com\/\d{7,9}',
      'youtube.com\/watch[?]v=(.*)',
      'youtu.be\/(.*)',
      'ted.com\/talks\/(.*)',
    ];
  }

  /**
   * Returns markup for a placeholder, based on the provider and the url.
   *
   * @param string $provider
   *   The provider, e.g. youtube.
   * @param string $url
   *   The full URL for the embedded content.
   *
   * @return string
   *   The placeholder, contains a string with markup.
   *
   * @throws \Exception
   */
  public function getPlaceholderMarkupForProvider(string $provider, string $url) :string {
    // Generate a unique identifier, this will help our ajax call to understand
    // which placeholder to replace when a user gives consent.
    $uuid = $this->uuidGenerator->generate();
    // Use the provider, so we can differentiate the message and look and feel,
    // but also in the future once users give consent to load all content from
    // a provider, we can easily change the behaviour based on this..
    $provider_class = strtolower($provider);
    // Get the current user's account ID.
    $uid = $this->currentUser->id();
    // Build up the markup, since it's part of the Process functions,
    // we can use direct markup here.
    $output[] = [
      '#type' => 'inline_template',
      '#template' => '<div class="social-embed-container" id="social-embed-placeholder">
                <div id="social-embed-iframe-{{ uuid }}" class="social-embedded-btn social-embed-iframe-{{ provider_class }}">
                <svg class="badge__icon"><use xlink:href="#icon-visibility_off"></use></svg>
                  <p class="social-embed-placeholder-body">{% trans %} By clicking show content, you agree to load the embedded content from <b>"{{ provider }}"</b> and therefore its privacy policy. {% endtrans %}<p>
                  <div><a class="use-ajax btn btn-primary waves-effect waves-btn social-embed-placeholder-btn" href="/api/opensocial/social-embed/generate?url={{ url }}&uuid={{ uuid }}">{% trans %} Show content {% endtrans %}</a></div>
                  {% if uid %}
                  <div><a class="social-embed-content-settings" href="/user/{{ uid }}/edit">{% trans %} View and edit embedded content settings {% endtrans %}</a></div>
                  {% endif %}
                </div>
              </div>',
      '#context' => [
        'uuid' => $uuid,
        'provider' => $provider,
        'provider_class' => $provider_class,
        'url' => $url,
        'uid' => $uid,
      ],
    ];

    return $this->renderer->render($output);
  }

}
