<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Error;
use Drupal\filter\FilterProcessResult;
use Drupal\social_embed\Service\SocialEmbedHelper;
use Drupal\social_embed\SocialUrlEmbedHelperInterface;
use Drupal\url_embed\Plugin\Filter\UrlEmbedFilter;
use Drupal\url_embed\UrlEmbedInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to display embedded URLs based on data attributes.
 *
 * @Filter(
 *   id = "social_embed_url_embed",
 *   title = @Translation("Display embedded URLs with consent"),
 *   description = @Translation("Embeds URLs using data attribute: data-embed-url."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class SocialEmbedUrlEmbedFilter extends UrlEmbedFilter {

  /**
   * Uuid services.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected UuidInterface $uuid;

  /**
   * The config factory services.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected ConfigFactory $configFactory;

  /**
   * The social embed helper services.
   *
   * @var \Drupal\social_embed\Service\SocialEmbedHelper
   */
  protected SocialEmbedHelper $embedHelper;

  /**
   * Current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Social Url Embed service.
   *
   * @var \Drupal\social_embed\SocialUrlEmbedHelperInterface
   */
  protected SocialUrlEmbedHelperInterface $socialUrlEmbedHelper;

  /**
   * Constructs a SocialEmbedUrlEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\url_embed\UrlEmbedInterface $url_embed
   *   The URL embed service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid services.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory services.
   * @param \Drupal\social_embed\Service\SocialEmbedHelper $embed_helper
   *   The social embed helper class object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer services.
   * @param \Drupal\social_embed\SocialUrlEmbedHelperInterface $social_url_embed_helper
   *   The url embed services.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UrlEmbedInterface $url_embed,
    UuidInterface $uuid,
    ConfigFactory $config_factory,
    SocialEmbedHelper $embed_helper,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $loggerFactory,
    protected RouteMatchInterface $routeMatch,
    Renderer $renderer,
    SocialUrlEmbedHelperInterface $social_url_embed_helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $url_embed, $renderer);

    $this->uuid = $uuid;
    $this->configFactory = $config_factory;
    $this->embedHelper = $embed_helper;
    $this->currentUser = $current_user;
    $this->loggerFactory = $loggerFactory;
    $this->socialUrlEmbedHelper = $social_url_embed_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('url_embed'),
      $container->get('uuid'),
      $container->get('config.factory'),
      $container->get('social_embed.helper_service'),
      $container->get('current_user'),
      $container->get('logger.factory'),
      $container->get('current_route_match'),
      $container->get('renderer'),
      $container->get('social_embed.url_embed_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    // Add settings in case we need to use _filter_url().
    $this->setConfiguration([
      'settings' => [
        'filter_url_length' => 72,
      ],
    ]);

    if (strpos($text, 'data-embed-url') !== FALSE) {
      // Load settings.
      $embed_settings = $this->configFactory->get('social_embed.settings');

      $dom = Html::load($text);
      /** @var \DOMXPath $xpath */
      $xpath = new \DOMXPath($dom);
      /** @var \DOMNode[] $matching_nodes */
      $matching_nodes = $xpath->query('//drupal-url[@data-embed-url]');

      $count = 1;
      foreach ($matching_nodes as $node) {
        /** @var \DOMElement $node */
        $url = $node->getAttribute('data-embed-url');

        // Abort if the URL is not whitelisted,
        // or if achieved max_embed_per_content.
        if (!$this->embedHelper->isWhitelisted($url) || $count > $embed_settings->get('max_embeds_per_content')) {
          $this->replaceNodeContent($node, _filter_url($url, $this));
          continue;
        }

        $url_output = '';
        try {
          $info = $this->socialUrlEmbedHelper->getUrlInfo($url);
          /** @var \Drupal\user\Entity\User $user */
          $user = $this->currentUser->isAnonymous() ? NULL : User::load($this->currentUser->id());
          if (!empty($info['code'])
            // Do not add consent button on embed preview during adding url.
            && $this->routeMatch->getRouteName() != 'embed.preview'
            && (($user instanceof User
                && $embed_settings->get('embed_consent_settings_lu')
                && $user->hasField('field_user_embed_content_consent')
                && !empty($user->get('field_user_embed_content_consent')->getValue()[0]['value']))
              || ($user == NULL && !empty($embed_settings->get('embed_consent_settings_an')))
            )
          ) {
            // Replace URL with consent button.
            $url_output = $this->embedHelper->getPlaceholderMarkupForProvider($info['providerName'], $url);
          }
          else {
            $url_output = $info['code'] ?? $url;
          }
        }
        catch (\Exception $e) {
          $logger = $this->loggerFactory->get('social_embed');
          Error::logException($logger, $e);
        } finally {
          // If the $url_output is empty, that means URL is non-embeddable.
          // So, we return the original url instead of blank output.
          if ($url_output == NULL || $url_output == '') {
            // The reason of using _filter_url() function here is to make
            // sure that the maximum URL cases e.g., emails are covered.
            $url_output = UrlHelper::isValid($url) ? _filter_url($url, $this) : $url;
          }
        }

        $this->replaceNodeContent($node, $url_output);
        $count++;
      }

      $result->setProcessedText(Html::serialize($dom));
    }
    // Add the required dependencies and cache tags.
    return $this->embedHelper->addDependencies($result, 'social_embed:filter.url_embed');
  }

}
