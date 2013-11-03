<?php

/**
 * @file
 * Definition of Drupal\social\Plugin\field\formatter\SocialFacebookFormatter.
 */

namespace Drupal\social\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Json;

/**
 * Plugin implementation of the 'social_facebook' formatter.
 *
 * @FieldFormatter(
 *   id = "social_facebook",
 *   label = @Translation("Facebook"),
 *   field_types = {
 *     "social_facebook"
 *   },
 *   settings = {
 *     "count" = "0"
 *   }
 * )
 */
class SocialFacebookFormatter extends DefaultSocialFormatter {

  /**
   * {@inheritdoc}
   */
  protected function socialCommentRender($url) {
    $config = \Drupal::config('social.settings');

    $this->app_id = $config->get('facebook_app_id');
    $this->app_secret = $config->get('facebook_app_secret');
    $this->expire = $config->get('facebook_cache');

    $this->getAccessToken();
    $post_id = $this->getPostId($url);
    $comments = $this->getComments($post_id);
    $output = $this->renderComments($comments);

    return $output;
  }

  /**
   * Get access token.
   *
   * @return string
   *   String containing access token.
   */
  public function getAccessToken() {
    // Set cache key.
    $cache_key = 'social_comments:facebook_access_token';

    $token = NULL;

    // Try to get comments from cache.
    if ($cache = cache()->get($cache_key)) {
      $token = $cache->data;
    }
    else {
      $response_url = url(
        'https://graph.facebook.com/oauth/access_token',
        array(
          'query' => array(
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'grant_type' => 'client_credentials',
          ),
        )
      );

      $request = \Drupal::httpClient()->get($response_url);

      try {
        $response = $request->send();
        $data = $response->getBody(TRUE);
      }
      catch (\Exception $e) {
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = '';
      if ($data && strpos($data, 'access_token') === 0) {
        $token = drupal_substr($data, 13);

        // Set data to cache.
        cache()->set($cache_key, $token);
      }
    }

    $this->access_token = $token;
  }

  /**
   * Parse post URL and get post ID.
   *
   * @param string $url
   *   Facebook post URL.
   *
   * @return string
   *   Post ID.
   */
  public function getPostId($url) {
    $id = FALSE;

    if (is_string($url)) {
      // Get URL path.
      $url = parse_url($url);
      if (!empty($url['query'])) {
        parse_str($url['query'], $query);
      }

      if (isset($query['fbid'])) {
        $id = $query['fbid'];
      }
    }

    return $id;
  }

  /**
   * Get facebook comments by post ID.
   *
   * @param string $id
   *   Post ID.
   *
   * @return array
   *  Array with comments.
   */
  public function getComments($id) {
    $comments = array();
    // Set cache key for each post id.
    $cache_key = 'social_comments:' . $this->type . ':' . $this->id . ':' . $this->viewMode . ':facebook:' . $id;

    // Try to get comments fom cache.
    if ($cache = cache()->get($cache_key)) {
      $comments = $cache->data;
    }
    else {
      $query = array(
        'access_token' => $this->access_token,
        'limit' => !empty($this->max_items) ? $this->max_items : NULL,
      );
      $query = array_filter($query);

      $response_url = url(
        'https://graph.facebook.com/' . $id . '/comments',
        array(
          'query' => $query,
        )
      );
      $request = \Drupal::httpClient()->get($response_url);

      try {
        $response = $request->send();
        $data = $response->getBody(TRUE);
      }
      catch (\Exception $e) {
        drupal_set_message(t('Facebook comments error'), 'warning');
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = Json::decode($data);

      if (!empty($result['data'])) {
        $comments = $result['data'];
        // Set data to cache.
        cache()->set($cache_key, $comments, $this->expire + REQUEST_TIME);
      }
    }

    return $comments;
  }

  /**
   * Parse facebook comments response.
   *
   * @param array $items
   *   JSON decoded string.
   *
   * @return array
   *   Array with comments.
   */
  public function renderComments($items) {
    $comments = array();

    if (isset($items['data'])) {
      $items = $items['data'];
    }

    if (is_array($items)) {
      foreach ($items as $item) {
        $data = array();

        // Get user data.
        $user = !empty($item['from']) ? $item['from'] : NULL;

        $userid = !empty($user['id']) ? check_plain($user['id']) : NULL;

        $data['id'] = check_plain($item['id']);
        $data['username'] = !empty($user['name']) ? check_plain($user['name']) : NULL;
        $data['userphoto'] = !empty($userid) ? $this->getUserPhoto($userid) : NULL;
        $data['text'] = filter_xss($item['message']);
        $data['timestamp'] = strtotime($item['created_time']);

        $comments[] = $data;
      }
    }

    $output = theme(
      'social_items',
      array(
        'comments' => $comments,
        'bundle' => $this->bundle,
        'type' => $this->type,
        'view_mode' => $this->viewMode,
      )
    );

    return $output;
  }

  public function getUserPhoto($userid) {
    $response_url = 'http://graph.facebook.com/' . $userid . '/picture';
    return $response_url;
  }
}
