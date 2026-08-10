<?php

namespace Drupal\social_embed\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;

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
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructor for SocialEmbedHelper.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user object.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer services.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(UuidInterface $uuid_generator, AccountProxyInterface $current_user, Renderer $renderer, ModuleHandlerInterface $module_handler) {
    $this->uuidGenerator = $uuid_generator;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
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
   * Checks if a given URL is whitelisted.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   Returns TRUE if the URL is whitelisted, otherwise FALSE.
   */
  public function isWhitelisted(string $url): bool {
    $parts = $this->parseEmbeddableUrl($url);
    if ($parts === NULL) {
      return FALSE;
    }

    $provider = $this->getProviderHost($parts['host']);
    if ($provider === NULL) {
      return FALSE;
    }

    // Rebuild from the trusted parts, so the patterns only decide whether this
    // is embeddable content. Matching the original URL would let a provider
    // name in a query string stand in for the path.
    $target = $provider . $parts['path'];
    if ($parts['query'] !== '') {
      $target .= '?' . $parts['query'];
    }

    return preg_match($this->getAnchoredPatterns(), $target) === 1;
  }

  /**
   * Resolves the host a URL would be requested from.
   *
   * @param string $url
   *   The URL to resolve the host for.
   *
   * @return string|null
   *   The lower-cased host without its "www." prefix, or NULL when the URL is
   *   not a plain web URL with a host we can trust.
   */
  public function getUrlHost(string $url): ?string {
    return $this->parseEmbeddableUrl($url)['host'] ?? NULL;
  }

  /**
   * Resolves the provider a host belongs to.
   *
   * Sub domains are matched on the dot boundary, so "m.youtube.com" resolves
   * while "youtube.com.example.com" does not.
   *
   * @param string $host
   *   The host to resolve, as returned by ::getUrlHost().
   *
   * @return string|null
   *   The allowed host this host belongs to, or NULL for none of them.
   */
  public function getProviderHost(string $host): ?string {
    foreach ($this->getAllowedHosts() as $allowed) {
      if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
        return $allowed;
      }
    }

    return NULL;
  }

  /**
   * Splits a URL into the parts that decide whether we can embed it.
   *
   * @param string $url
   *   The URL to split.
   *
   * @return array{host: string, path: string, query: string}|null
   *   The parsed URL, or NULL when it is not a plain web URL with a host we
   *   can trust.
   */
  protected function parseEmbeddableUrl(string $url): ?array {
    // The convert filter also picks up URLs typed without a scheme, treat
    // those as https so the host is not parsed as part of the path.
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url) !== 1) {
      $url = 'https://' . ltrim($url, '/');
    }

    $parts = parse_url($url);
    if ($parts === FALSE || empty($parts['host'])) {
      return NULL;
    }

    // Embedding only makes sense over http(s), this keeps file:// and the
    // like out.
    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], TRUE)) {
      return NULL;
    }

    // The port is not part of what we match on, so refuse anything but the
    // default to keep it from reaching the request unchecked.
    if (isset($parts['port']) && $parts['port'] !== ($scheme === 'https' ? 443 : 80)) {
      return NULL;
    }

    // Credentials are only ever used to disguise the real host, as in
    // "http://youtube.com@127.0.0.1/".
    if (isset($parts['user']) || isset($parts['pass'])) {
      return NULL;
    }

    $host = strtolower(rtrim($parts['host'], '.'));

    return [
      'host' => str_starts_with($host, 'www.') ? substr($host, 4) : $host,
      'path' => $parts['path'] ?? '',
      'query' => $parts['query'] ?? '',
    ];
  }

  /**
   * The hosts the whitelisted patterns belong to.
   *
   * @return string[]
   *   The list of allowed hosts, without their "www." prefix.
   */
  public function getAllowedHosts(): array {
    return [
      'facebook.com',
      'flickr.com',
      'flic.kr',
      'instagram.com',
      'open.spotify.com',
      'twitter.com',
      'x.com',
      'vimeo.com',
      'youtube.com',
      'youtu.be',
      'ted.com',
    ];
  }

  /**
   * A list of whitelisted patterns.
   *
   * @return array
   *   The list of patterns.
   */
  public function getPatterns(): array {
    return [
      '(facebook.com\/(.*)\/videos\/(.*))',
      '(facebook.com\/(.*)\/photos\/(.*))',
      '(facebook.com\/(.*)\/posts\/(.*))',
      '(flickr.com\/photos\/(.*))',
      '(flic.kr\/p\/(.*))',
      '(instagram.com\/p\/(.*))',
      '(open.spotify.com\/track\/(.*))',
      '(twitter.com\/(.*)\/status\/(.*))',
      '(x.com\/(.*)\/status\/(.*))',
      '(vimeo.com\/[a-zA-Z0-9]+(?:\/[a-zA-Z0-9]+)?\/?)',
      '(youtube.com\/watch[?]v=(.*))',
      '(youtu.be\/(.*))',
      '(ted.com\/talks\/(.*))',
    ];
  }

  /**
   * The combined version of the patterns.
   *
   * @return string
   *   The string of combined patterns.
   */
  public function getCombinedPatterns(): string {
    // Fetch allowed patterns.
    $patterns = $this->getPatterns();

    // Combine the patterns in a single string.
    $combined_patterns = implode('|', $patterns);

    // Return the combined version of the patterns.
    return "/\b(?:https?:\/\/)?(?:www\.)?($combined_patterns)/i";
  }

  /**
   * The anchored version of the patterns, for validating a parsed URL.
   *
   * ::getCombinedPatterns() searches for a URL inside a larger text, so it
   * allows a scheme and "www." prefix. Here the URL is already parsed.
   *
   * @return string
   *   The anchored version of the patterns.
   *
   * @see \Drupal\social_embed\Service\SocialEmbedHelper::isWhitelisted()
   */
  public function getAnchoredPatterns(): string {
    $combined_patterns = implode('|', $this->getPatterns());

    return "/^($combined_patterns)/i";
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
  public function getPlaceholderMarkupForProvider(string $provider, string $url): string {
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
          <div><a class="use-ajax btn btn-primary waves-effect waves-btn social-embed-placeholder-btn" href="{{ base_path }}api/opensocial/social-embed/generate?url={{ url }}&uuid={{ uuid }}">{% trans %} Show content {% endtrans %}</a></div>
          {% if show_edit_link %}
          <div><a class="social-embed-content-settings" href="{{ base_path }}user/{{ uid }}/edit">{% trans %} View and edit embedded content settings {% endtrans %}</a></div>
          {% endif %}
        </div>
      </div>',
      '#context' => [
        'uuid' => $uuid,
        'provider' => $provider,
        'provider_class' => $provider_class,
        'url' => $url,
        'show_edit_link' => $uid,
        'base_path' => base_path(),
      ],
    ];

    $this->moduleHandler->alter('social_embed_placeholder', $output);

    return $this->renderer->render($output);
  }

}
