<?php

/**
 * @file
 * Twitter class
 */

class SocialTwitterFormatter {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->consumer_key = variable_get('social_twitter_consumer_key', '');
    $this->consumer_secret = variable_get('social_twitter_consumer_secret', '');
    $this->access_token = variable_get('social_twitter_access_token', '');
    $this->access_token_secret = variable_get('social_twitter_access_token_secret', '');
    $this->expire = variable_get('social_twitter_cache', 600);
  }

  /**
   * {@inheritdoc}
   */
  public function getData($url, $count) {
    $this->max_items = $count;
    $tweet_id = $this->getTweetId($url);
    $comments = $tweet_id ? $this->getComments($tweet_id) : FALSE;
    $output = $comments ? $this->renderComments($comments) : array();

    return $output;
  }

  /**
   * Get tweet ID from URL.
   *
   * @param string $url
   *   Tweet URL.
   *
   * @return string
   *   Tweet ID.
   */
  function getTweetId($url) {
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
   * Return twitter comments.
   */
  function getComments($id) {
    $comments = array();
    $cache_key = 'social_comments:twitter:' . $id;

    if ($cache = cache_get($cache_key)) {
      $comments = $cache->data;
    }
    else {
      if (libraries_load('acTwitterConversation')) {
        $connection = new acTwitterConversation;
        $comments = $connection->fetchConversion($id);

        if (!$comments['error']) {
          $comments = $comments['tweets'];
          cache_set($cache_key, $comments, 'cache', $this->expire + REQUEST_TIME);
        }
      }
    }

    return $comments;
  }

  /**
   * Parse twitter comments.
   */
  function renderComments($items) {
    $comments = array();

    if (is_array($items)) {
      if (!empty($this->max_items)) {
        $items = array_slice($items, 0, $this->max_items);
      }
      foreach ($items as $item) {
        $data = array();

        // Get user data.
        $data['id'] = check_plain($item['id']);
        $data['username'] = !empty($item['name']) ? check_plain($item['name']) : NULL;
        $data['user_url'] = !empty($item['username']) ? url('https://twitter.com/' . $item['username']) : NULL;
        $data['userphoto'] = !empty($item['images']['thumbnail']) ? filter_xss($item['images']['thumbnail']) : NULL;
        $data['text'] = filter_xss($item['content']);
        $data['timestamp'] = strtotime($item['date']);

        $comments[] = $data;
      }
    }

    return $comments;
  }
}