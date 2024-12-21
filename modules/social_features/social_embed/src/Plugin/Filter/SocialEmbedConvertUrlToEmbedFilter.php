<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Error;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\social_embed\Service\SocialEmbedHelper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "social_embed_convert_url",
 *   title = @Translation("Convert SUPPORTED URLs to URL embeds"),
 *   description = @Translation("Convert only URLs that are supported to URL embeds."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {}
 * )
 */
class SocialEmbedConvertUrlToEmbedFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The social embed helper services.
   *
   * @var \Drupal\social_embed\Service\SocialEmbedHelper
   */
  protected SocialEmbedHelper $embedHelper;

  /**
   * Constructs a SocialEmbedConvertUrlToEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_embed\Service\SocialEmbedHelper $embed_helper
   *   The social embed helper class object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SocialEmbedHelper $embed_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->embedHelper = $embed_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_embed.helper_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    // Convert Urls.
    $pattern = $this->embedHelper->getCombinedPatterns();
    $result = new FilterProcessResult($this->convertUrls($text, $pattern));

    // Not whitelisted is return the string as is.
    // Also, add the required dependencies and cache tags.
    return $this->embedHelper->addDependencies($result, 'social_embed:filter.convert_url');
  }

  /**
   * Replaces appearances of supported URLs with <drupal-url> embed elements.
   *
   * Logic of this function is copied from _filter_url() and slightly adopted
   * for our use case. _filter_url() is unfortunately not general enough to
   * re-use it.
   *
   * @param string $text
   *   Text to be processed.
   * @param string $pattern
   *   URL pattern to match.
   *
   * @return string
   *   Processed text.
   */
  public function convertUrls(string $text, string $pattern): string {
    // Tags to skip and not recurse into.
    $ignore_tags = 'a|script|style|code|pre';

    // Split at all tags; ensures that no tags or attributes are processed.
    $chunks = !$text ? [''] : preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Do not attempt to convert links into URLs if preg_split() fails.
    if ($chunks !== FALSE) {
      // PHP ensures that the array consists of alternating delimiters and
      // literals, and begins and ends with a literal (inserting NULL as
      // required). Therefore, the first chunk is always text:
      $chunk_type = 'text';
      // If a tag of $ignore_tags is found, it is stored in $open_tag and only
      // removed when the closing tag is found. Until the closing tag is found,
      // no replacements are made.
      $open_tag = '';
      for ($i = 0; $i < count($chunks); $i++) {
        if ($chunk_type == 'text') {
          // Only process this text if there are no unclosed $ignore_tags.
          if ($open_tag == '') {
            // If there is a match, inject a link into this chunk via the
            // callback function contained in $task.
            $chunks[$i] = preg_replace_callback(
              $pattern,
              function ($match) {
                // Replace URL by the embed code.
                return self::socialUrlToEmbed($match[1]);
              },
              (string) $chunks[$i]
            );
          }
          // Text chunk is done, so next chunk must be a tag.
          $chunk_type = 'tag';
        }
        else {
          // Only process this tag if there are no unclosed $ignore_tags.
          if ($open_tag == '') {
            // Check whether this tag is contained in $ignore_tags.
            if (preg_match("`<($ignore_tags)(?:\s|>)`i", (string) $chunks[$i], $matches)) {
              $open_tag = $matches[1];
            }
          }
          // Otherwise, check whether this is the closing tag for $open_tag.
          else {
            if (preg_match("`<\/$open_tag>`i", (string) $chunks[$i], $matches)) {
              $open_tag = '';
            }
          }
          // Tag chunk is done, so next chunk must be text.
          $chunk_type = 'text';
        }
      }

      $text = implode($chunks);
    }

    return $text;
  }

  /**
   * Replace URL by the embed code.
   *
   * @param string $url
   *   The matched url.
   *
   * @return string
   *   Processed link.
   */
  public static function socialUrlToEmbed(string $url): string {
    // Add protocol if does not exist.
    if (!preg_match('/^https?:\/\//', $url)) {
      $url = 'https://' . $url;
    }

    try {
      $url_for_processing = $url;
      $social_embed_helper = \Drupal::service('social_embed.helper_service');

      // Decode URL.
      $url_for_processing = Html::decodeEntities($url_for_processing);

      // Default URL for return.
      $result_link = $url_for_processing;

      // Full URL with protocol (http, https etc.).
      $info = \Drupal::service('url_embed')->getUrlInfo($url);

      if ($info) {
        /** @var \Drupal\user\Entity\User $user */
        $user = \Drupal::currentUser()->isAnonymous() ? NULL : User::load(\Drupal::currentUser()->id());
        $embed_settings = \Drupal::configFactory()->get('social_embed.settings');

        if (!empty($info['code'])
          && (($user instanceof User
              && $user->hasField('field_user_embed_content_consent')
              && !empty($user->get('field_user_embed_content_consent')->getValue()[0]['value'])
              && $embed_settings->get('embed_consent_settings_lu'))
            || ($user == NULL && !empty($embed_settings->get('embed_consent_settings_an')))
          )
        ) {
          // Replace URL with consent button.
          return $social_embed_helper->getPlaceholderMarkupForProvider($info['providerName'], $url);
        }
        else {
          // For "Facebook" and "Instagram" links embedding require to
          // set up an application. Sometimes it doesn't have a sake
          // to do it. Let's return a link without embedding
          // if the application isn't connected.
          if (
            preg_match("/facebook.com\/|instagram.com\//i", $result_link) &&
            (
              !\Drupal::config('url_embed.settings')->get('facebook_app_id') ||
              !\Drupal::config('url_embed.settings')->get('facebook_app_secret')
            )
          ) {
            return $result_link;
          }

          $result_link = '<drupal-url data-embed-url="' . $url . '"></drupal-url>';
        }
      }

      return $result_link;
    }
    catch (\Exception $e) {
      // If anything goes wrong while retrieving remote data, catch
      // the exception to avoid a WSOD and leave the URL as is.
      $logger = \Drupal::logger('social_embed');
      Error::logException($logger, $e);

      return $url;
    }
  }

}
