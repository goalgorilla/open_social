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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
    // Check for whitelisted URL.
    if ($this->embedHelper->whiteList($text)) {
      $result = new FilterProcessResult(static::convertUrls($text));
    }
    else {
      $result = new FilterProcessResult($text);
    }
    // Not whitelisted is return the string as is.
    // Also, add the required dependencies and cache tags.
    return $this->embedHelper->addDependencies($result, 'social_embed:filter.convert_url');
  }

  /**
   * Replaces appearances of supported URLs with placeholder embed elements.
   *
   * Logic of this function is copied from _filter_url() and slightly adopted
   * for our use case. _filter_url() is unfortunately not general enough to
   * re-use it.
   *
   * If something wrong here, you can reference to _filter_url() function.
   *
   * @param string $text
   *   Text to be processed.
   *
   * @return mixed
   *   Processed text.
   */
  public static function convertUrls(string $text): mixed {
    // Store the current text in case any of the preg_* functions fail.
    $saved_text = $text;

    // Tags to skip and not recurse into.
    $ignore_tags = 'a|script|style|code|pre';

    // Prepare protocols pattern for absolute URLs.
    // \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
    // will replace any bad protocols with HTTP, so we need to support the
    // identical list.
    // While '//' is technically optional for MAILTO only, we cannot cleanly
    // differ between protocols here without hard-coding MAILTO, so '//' is
    // optional for all protocols.
    // @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
    $protocols = \Drupal::getContainer()->getParameter('filter_protocols');
    assert(is_string($protocols) || is_array($protocols), "Invalid filter_protocols parameter configuration, must be either array or string");
    $protocols = is_array($protocols) ? implode(':(?://)?|', $protocols) . ':(?://)?' : $protocols;

    $valid_url_path_characters = "[\p{L}\p{M}\p{N}!\*\';:=\+,\.\$\/%#\[\]\-_~@&]";

    // Allow URL paths to contain balanced parens
    // 1. Used in Wikipedia URLs like /Primer_(film)
    // 2. Used in IIS sessions like /S(dfd346)/.
    $valid_url_balanced_parens = '\(' . $valid_url_path_characters . '+\)';

    // Valid end-of-path characters (so /foo. does not gobble the period).
    // 1. Allow =&# for empty URL parameters and other URL-join artifacts.
    $valid_url_ending_characters = '[\p{L}\p{M}\p{N}:_+~#=/]|(?:' . $valid_url_balanced_parens . ')';

    $valid_url_query_chars = '[a-zA-Z0-9!?\*\'@\(\);:&=\+\$\/%#\[\]\-_\.,~|]';
    $valid_url_query_ending_chars = '[a-zA-Z0-9_&=#\/]';

    // Full path and allow @ in a url, but only in the middle. Catch things
    // like http://example.com/@user/
    $valid_url_path = '(?:(?:' . $valid_url_path_characters . '*(?:' . $valid_url_balanced_parens . $valid_url_path_characters . '*)*' . $valid_url_ending_characters . ')|(?:@' . $valid_url_path_characters . '+\/))';

    // Prepare domain name pattern.
    // The ICANN seems to be on track towards accepting more diverse top level
    // domains, so this pattern has been "future-proofed" to allow for TLDs
    // of length 2-64.
    $domain = '(?:[\p{L}\p{M}\p{N}._+-]+\.)?[\p{L}\p{M}]{2,64}\b';
    $ip = '(?:[0-9]{1,3}\.){3}[0-9]{1,3}';
    $auth = '[\p{L}\p{M}\p{N}:%_+*~#?&=.,/;-]+@';
    $trail = '(' . $valid_url_path . '*)?(\\?' . $valid_url_query_chars . '*' . $valid_url_query_ending_chars . ')?';

    // Match absolute URLs.
    $url_pattern = "(?:$auth)?(?:$domain|$ip)\/?(?:$trail)?";
    $pattern = "`((?:$protocols)(?:$url_pattern))`u";
    $tasks['socialFullUrlToEmbed'] = $pattern;

    // Match www. domains.
    $url_pattern = "www\.(?:$domain)\/?(?:$trail)?";
    $pattern = "`($url_pattern)`u";
    $tasks['socialPartialUrlToEmbed'] = $pattern;

    // Match short whitelisted domains (without http/https and www.).
    $url_pattern = implode("|", \Drupal::service('social_embed.helper_service')->getPatterns());
    $pattern = "`($url_pattern)`u";
    $tasks['socialShortUrlToEmbed'] = $pattern;

    // Each type of URL needs to be processed separately. The text is joined and
    // re-split after each task, since all injected HTML tags must be correctly
    // protected before the next task.
    foreach ($tasks as $task => $pattern) {
      // HTML comments need to be handled separately, as they may contain HTML
      // markup, especially a '>'. Therefore, remove all comment contents and
      // add them back later.
      _filter_url_escape_comments([], TRUE);

      $text = ($text !== NULL) ? preg_replace_callback('`<!--(.*?)-->`s', '_filter_url_escape_comments', $text) : $text;

      // Split at all tags; ensures that no tags or attributes are processed.
      $chunks = ($text !== NULL) ? preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE) : [];

      // Do not attempt to convert links into URLs if preg_split() fails.
      if ($chunks !== FALSE) {
        // PHP ensures that the array consists of alternating delimiters and
        // literals, and begins and ends with a literal (inserting NULL as
        // required). Therefore, the first chunk is always text:
        $chunk_type = 'text';
        // If a tag of $ignore_tags is found, it is stored in $open_tag and only
        // removed when the closing tag is found. Until the closing tag is
        // found,no replacements are made.
        $open_tag = '';
        for ($i = 0; $i < count($chunks); $i++) {
          if ($chunk_type == 'text') {
            // Only process this text if there are no unclosed $ignore_tags.
            if ($open_tag == '') {
              // If there is a match, inject a link into this chunk via the
              // callback function contained in $task.
              if ($chunks[$i] !== NULL) {
                $chunks[$i] = preg_replace_callback($pattern, [static::class, $task], $chunks[$i]);
              }
            }
            // Text chunk is done, so next chunk must be a tag.
            $chunk_type = 'tag';
          }
          else {
            // Only process this tag if there are no unclosed $ignore_tags.
            if ($open_tag == '') {
              // Check whether this tag is contained in $ignore_tags.
              if (preg_match("`<($ignore_tags)(?:\s|>)`i", $chunks[$i], $matches)) {
                $open_tag = $matches[1];
              }
            }
            // Otherwise, check whether this is the closing tag for $open_tag.
            else {
              if (preg_match("`<\/$open_tag>`i", $chunks[$i], $matches)) {
                $open_tag = '';
              }
            }
            // Tag chunk is done, so next chunk must be text.
            $chunk_type = 'text';
          }
        }

        $text = implode($chunks);
      }

      // Revert to the original comment contents.
      _filter_url_escape_comments([], FALSE);

      if ($text !== NULL) {
        $text = preg_replace_callback('`<!--(.*?)-->`', '_filter_url_escape_comments', $text);
      }
    }

    // If there is no text at this point revert to the previous text.
    return strlen((string) $text) > 0 ? $text : $saved_text;
  }

  /**
   * Makes links out of absolute URLs.
   *
   * Callback for preg_replace_callback() within convertUrls().
   */
  public static function socialFullUrlToEmbed(array $match): string {
    return self::socialUrlToEmbed($match);
  }

  /**
   * Makes links out of domain names starting with "www.".
   *
   * Callback for preg_replace_callback() within convertUrls().
   */
  public static function socialPartialUrlToEmbed(array $match): string {
    return self::socialUrlToEmbed($match, 'https://');
  }

  /**
   * Makes links out of domain names starting with domain name.
   *
   * Callback for preg_replace_callback() within convertUrls().
   */
  public static function socialShortUrlToEmbed(array $match): string {
    return self::socialPartialUrlToEmbed($match);
  }

  /**
   * Process URL for embedding.
   *
   * @param array $match
   *   The array with matched strings.
   * @param string $full_url_prefix
   *   The internet protocol
   *   Send your value (https:// or https://www.) for partial and short links.
   *
   * @return string
   *   Processed link.
   */
  public static function socialUrlToEmbed(array $match, string $full_url_prefix = ''): string {
    try {
      $url_for_processing = $match[1];
      $social_embed_helper = \Drupal::service('social_embed.helper_service');
      // Decode URL.
      $url_for_processing = Html::decodeEntities($url_for_processing);
      // Default URL for return.
      $result_link = $url_for_processing;
      // Full URL with protocol (http, https etc.).
      $full_url = $full_url_prefix . $url_for_processing;
      $info = \Drupal::service('url_embed')->getUrlInfo($full_url);

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
          return $social_embed_helper->getPlaceholderMarkupForProvider($info['providerName'], $full_url);
        }
        else {
          $white_list = $social_embed_helper->getPatterns();

          // Check if the URL provided is from a whitelisted site.
          foreach ($white_list as $item) {
            // Testing pattern.
            $testing_pattern = '/' . $item . '/';

            // Check if it matches.
            if (preg_match($testing_pattern, $url_for_processing)) {
              $result_link = '<drupal-url data-embed-url="' . $full_url . '"></drupal-url>';
            }
          }
        }
      }

      return $result_link;
    }
    catch (\Exception $e) {
      // If anything goes wrong while retrieving remote data, catch
      // the exception to avoid a WSOD and leave the URL as is.
      $logger = \Drupal::logger('social_embed');
      Error::logException($logger, $e);

      return $match[1];
    }
  }

}
