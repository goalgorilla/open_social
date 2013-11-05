<?php

/**
 * @file
 * Definition of Drupal\social\Plugin\field\formatter\SocialTwitterFormatter.
 */

namespace Drupal\social\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Json;

/**
 * Plugin implementation of the 'social_twitter' formatter.
 *
 * @FieldFormatter(
 *   id = "social_twitter",
 *   label = @Translation("Twitter"),
 *   field_types = {
 *     "social_twitter"
 *   },
 *   settings = {
 *     "count" = "0"
 *   }
 * )
 */
class SocialTwitterFormatter extends DefaultSocialFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $elements = parent::settingsForm($form, $form_state);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function socialCommentRender($url) {
    $config = \Drupal::config('social.settings');

    $this->api_key = $config->get('google_api_key');
    $this->expire = $config->get('google_cache');

    $tweet_id = $this->getTweetId($url);
    $comments = $this->getComments($tweet_id);
    $output = $this->renderComments($comments);

    return $output;
  }

  /**
   * Get tweet ID from URL.
   *
   * @param string $url
   *   Tweet URL.
   *
   * @return mixed
   *   Tweet ID if success.
   *   FALSE if failure.
   */
  public function getTweetId($url) {
    $tweet_id = FALSE;

    if (is_string($url)) {
      // Get URL path.
      $url = parse_url($url, PHP_URL_PATH);

      // Explode for arguments.
      $args = explode('/', $url);

      if (isset($args[3]) && is_numeric($args[3])) {
        $tweet_id = $args[3];
      }
    }

    return $tweet_id;
  }

  /**
   * Get tweet comments by tweet ID.
   *
   * @param string $tweet_id
   *   Tweet ID.
   *
   * @return array
   *   Tweet comments response.
   */
  public function getComments($tweet_id) {
    // Set cache key for each tweet.
    $cache_key = 'social_comments:' . $this->entity_type . ':' . $this->id . ':' . $this->viewMode . ':twitter:' . $tweet_id;

    // Try to get comments fom cache.
    if ($cache = cache()->get($cache_key)) {
      $comments = $cache->data;
    }
    else {
      $response_url = 'https://api.twitter.com/1.1/statuses/show.json';

      $headers = array(
        'id' => $tweet_id,
        'oauth_consumer_key' => "Ths69bN5IbPTPGufXslrHg",
        'oauth_nonce' => "kYjzVBB8Y0ZFabxSWbWovY3uYSQ2pTgmZeNu2VS4cg",
        'oauth_signature' => "tnnArxj06cWHq44gCs1OSKk%2FjLY%3D",
        'oauth_signature_method' => "HMAC-SHA1",
        'oauth_timestamp' => "1318622958",
        'oauth_token' => "370773112-GmHxMAgYyLbNEtIKZeRNFsMKPR9EyMZeS9weJAEb",
        'oauth_version' => "1.0",
      );

      $request = \Drupal::httpClient()->post($response_url, $headers);

      try {
        $response = $request->send();
        $data = $response->getBody(TRUE);
      }
      catch (\Exception $e) {
        drupal_set_message(t('Twitter comments error'), 'warning');
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      // Decode response and parse it.
      $comments = Json::decode($data);

      // Set data to cache.
      cache()->set($cache_key, $comments, $this->expire + REQUEST_TIME);
    }

    return $comments;
  }

  /**
   * Collect data from twitter response.
   *
   * @param array $items
   *   JSON decoded response string.
   *
   * @return array
   *   Array with comments.
   */
  public function renderComments($items) {
    $comments = array();

    if (count($items) == 1 && isset($items[0])) {
      $items = $items[0]['results'];
    }

    if (is_array($items)) {
      foreach ($items as $item) {
        $data = array();
        $item = $item['value'];

        // Get user data.
        $user = !empty($item['user']) ? $item['user'] : NULL;

        $data['id'] = check_plain($item['id_str']);
        $data['username'] = !empty($user['name']) ? check_plain($user['name']) : NULL;
        $data['userphoto'] = !empty($user['profile_image_url']) ? filter_xss($user['profile_image_url']) : NULL;
        $data['text'] = filter_xss($item['text']);
        $data['timestamp'] = strtotime($item['created_at']);

        $comments[] = $data;
      }
    }

    $output = theme(
      'social_items',
      array(
        'comments' => $comments,
        'bundle' => $this->bundle,
        'entity_type' => $this->entity_type,
        'type' => 'twitter',
        'view_mode' => $this->viewMode,
      )
    );

    return $output;
  }
}
