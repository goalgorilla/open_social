<?php

/**
 * @file
 * Facebook class
 */

class SocialFacebookFormatter {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->app_id = variable_get('social_facebook_app_id');
    $this->app_secret = variable_get('social_facebook_app_secret');
    $this->expire = variable_get('social_facebook_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function getData($url, $count) {
    $this->max_items = $count;
    $this->access_token = $this->getAccessToken();
    $post_id = $this->access_token ? $this->getPostId($url) : FALSE;
    $comments = $post_id ? $this->getComments($post_id) : FALSE;
    $output = $comments ? $this->renderComments($comments) : array();

    return $output;
  }

  /**
   * Get access token.
   *
   * @return string
   *   String containing access token.
   */
  protected function getAccessToken() {
    // Set cache key.
    $cache_key = 'social_comments:facebook_access_token';

    $token = NULL;

    // Try to get comments from cache.
    if ($cache = cache_get($cache_key)) {
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

      $data = drupal_http_request($response_url);

      if ($data->code != 200) {
        watchdog('social_comments', $data->error, array(), WATCHDOG_WARNING);
        return FALSE;
      }

      if ($data->data && strpos($data->data, 'access_token') === 0) {
        $token = drupal_substr($data->data, 13);

        // Set data to cache.
        cache_set($cache_key, $token);
      }
    }

    return $token;
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
  protected function getPostId($url) {
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
   *   Array with comments.
   */
  protected function getComments($id) {
    $comments = array();
    // Set cache key for each post id.
    $cache_key = 'social_comments:facebook:' . $id;

    // Try to get comments fom cache.
    if ($cache = cache_get($cache_key)) {
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
      $data = drupal_http_request($response_url);

      if ($data->code != 200) {
        drupal_set_message(t('Facebook comments error'), 'warning');
        watchdog_exception('social_comments', $data->error, array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = drupal_json_decode($data->data);

      if (!empty($result['data'])) {
        $comments = $result['data'];
        // Set data to cache.
        cache_set($cache_key, $comments, 'cache', $this->expire + REQUEST_TIME);
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
  protected function renderComments($items) {
    $comments = array();

    if (isset($items['data'])) {
      $items = $items['data'];
    }

    if (is_array($items)) {
      if (!empty($this->max_items)) {
        $items = array_slice($items, 0, $this->max_items);
      }
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

    return $comments;
  }

  /**
   * Returns photo of user.
   */
  protected function getUserPhoto($userid) {
    $response_url = 'http://graph.facebook.com/' . $userid . '/picture';
    return $response_url;
  }
}
