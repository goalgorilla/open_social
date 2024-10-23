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
    // Fetch allowed patterns.
    $patterns = $this->getCombinedPatterns();

    // Check if the URL matches any of the allowed patterns.
    return preg_match($patterns, $url) === 1;
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
          <div><a class="use-ajax btn btn-primary waves-effect waves-btn social-embed-placeholder-btn" href="/api/opensocial/social-embed/generate?url={{ url }}&uuid={{ uuid }}">{% trans %} Show content {% endtrans %}</a></div>
          {% if show_edit_link %}
          <div><a class="social-embed-content-settings" href="/user/{{ uid }}/edit">{% trans %} View and edit embedded content settings {% endtrans %}</a></div>
          {% endif %}
        </div>
      </div>',
      '#context' => [
        'uuid' => $uuid,
        'provider' => $provider,
        'provider_class' => $provider_class,
        'url' => $url,
        'show_edit_link' => $uid,
      ],
    ];

    $this->moduleHandler->alter('social_embed_placeholder', $output);

    return $this->renderer->render($output);
  }

}
