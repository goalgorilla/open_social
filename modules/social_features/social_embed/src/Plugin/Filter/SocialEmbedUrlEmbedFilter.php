<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\social_embed\Service\SocialEmbedHelper;
use Drupal\url_embed\Plugin\Filter\UrlEmbedFilter;
use Drupal\url_embed\UrlEmbedInterface;
use Drupal\user\UserDataInterface;
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
   * @var \Drupal\Component\Uuid\Php
   */
  protected Php $uuid;

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
   * The user data object.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

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
   * @param \Drupal\Component\Uuid\Php $uuid
   *   The uuid services.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory services.
   * @param \Drupal\social_embed\Service\SocialEmbedHelper $embed_helper
   *   The social embed helper class object.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user date object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UrlEmbedInterface $url_embed,
    Php $uuid,
    ConfigFactory $config_factory,
    SocialEmbedHelper $embed_helper,
    UserDataInterface $user_data,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $url_embed);
    $this->uuid = $uuid;
    $this->configFactory = $config_factory;
    $this->embedHelper = $embed_helper;
    $this->userData = $user_data;
    $this->currentUser = $current_user;
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
      $container->get('user.data'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (strpos($text, 'data-embed-url') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//drupal-url[@data-embed-url]') as $node) {
        /** @var \DOMElement $node */
        $url = $node->getAttribute('data-embed-url');
        $url_output = '';
        $info = $this->urlEmbed->getUrlInfo($url);
        try {
          if ($this->configFactory->get('social_embed.settings')->get('embed_consent_settings')
            && $this->userData->get('social_embed', $this->currentUser->id(), 'user_embed_consent')
            && !empty($info['code'])
          ) {
            // Replace URL with consent button.
            $url_output = $this->embedHelper->getPlaceholderMarkupForProvider($info['providerName'], $url);
          }
          else {
            $url_output = $info['code'];
          }
        }
        catch (\Exception $e) {
          watchdog_exception('url_embed', $e);
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
      }

      $result->setProcessedText(Html::serialize($dom));
    }
    // Add the required dependencies and cache tags.
    return $this->embedHelper->addDependencies($result, 'social_embed:filter.url_embed');
  }

}
