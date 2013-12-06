<?php

/**
 * @file
 * Google class
 */

class SocialGoogleFormatter {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->api_key = variable_get('google_api_key');
    $this->expire = variable_get('google_cache');
  }

  /**
   * {@inheritdoc}
   */
  public function getData($url, $count) {
    $this->max_items = $count;
    $activity_id = $this->getActivityId($url);
    $comments = $activity_id ? $this->getComments($activity_id) : FALSE;
    $output = $comments ? $this->renderComments($comments) : array();

    return $output;
  }

  /**
   * Get activity ID from URL.
   *
   * @param string $url
   *   Google activity URL.
   *
   * @return string
   *   Activity ID.
   */
  protected function getActivityId($url) {
    // Get URL path.
    $url = parse_url($url, PHP_URL_PATH);
    // Explode for arguments.
    $args = explode('/', $url);

    $user_id = isset($args[1]) ? $args[1] : NULL;
    $post_key = isset($args[3]) ? $args[3] : NULL;

    $cache_key = 'social_comments:google:' . $post_key;
    $id = FALSE;

    if ($cache = cache_get($cache_key)) {
      $id = $cache->data;
    }
    else {
      $response_url = url(
        'https://www.googleapis.com/plus/v1/people/' . $user_id . '/activities/public',
        array(
          'query' => array(
            'key' => $this->api_key,
          ),
        )
      );

      $data = drupal_http_request($response_url);

      if ($data->code != 200) {
        watchdog('social_comments', $data->error, array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = drupal_json_decode($data->data);

      if (!empty($result['items'])) {
        foreach ($result['items'] as $item) {
          if (strpos($item['url'], $post_key) && strpos($item['url'], $user_id)) {
            $id = $item['id'];

            // Set data to cache.
            cache_set($cache_key, $id, 'cache', $this->expire + REQUEST_TIME);
            break;
          }
        }
      }
    }

    return $id;
  }

  /**
   * Get comments from activity ID.
   *
   * @param string $id
   *   Activity ID.
   *
   * @return array
   *   Array with comments.
   */
  protected function getComments($id) {
    $comments = array();
    $cache_key = 'social_comments:google:' . $id;

    if ($cache = cache_get($cache_key)) {
      $comments = $cache->data;
    }
    else {
      $query = array(
        'key' => $this->api_key,
      );
      $query = array_filter($query);

      $response_url = url(
        'https://www.googleapis.com/plus/v1/activities/' . $id . '/comments',
        array(
          'query' => $query,
        )
      );

      $data = drupal_http_request($response_url);

      if ($data->code != 200) {
        watchdog('social_comments', $data->error, array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = drupal_json_decode($data->data);

      if (!empty($result['items'])) {
        $comments = $result['items'];
        // Set data to cache.
        cache_set($cache_key, $comments, 'cache', $this->expire + REQUEST_TIME);
      }
    }

    return $comments;
  }

  /**
   * Collect data from google response.
   *
   * @param array $items
   *   JSON decoded response string.
   *
   * @return array
   *   Data with comments.
   */
  protected function renderComments($items) {
    $comments = array();

    if (is_array($items)) {
      if (!empty($this->max_items)) {
        $items = array_slice($items, 0, $this->max_items);
      }
      foreach ($items as $item) {
        $data = array();
        $comment = $item['object'];

        // Get user data.
        $user = !empty($item['actor']) ? $item['actor'] : NULL;

        $data['id'] = check_plain($item['id']);
        $data['username'] = !empty($user['displayName']) ? check_plain($user['displayName']) : NULL;
        $data['userphoto'] = !empty($user['image']['url']) ? filter_xss($user['image']['url']) : NULL;
        $data['text'] = filter_xss($comment['content']);
        $data['timestamp'] = strtotime($item['published']);

        $comments[] = $data;
      }
    }

    return $comments;
  }
}
