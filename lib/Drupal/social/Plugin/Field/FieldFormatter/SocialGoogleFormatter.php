<?php

/**
 * @file
 * Definition of Drupal\social\Plugin\field\formatter\SocialGoogleFormatter.
 */

namespace Drupal\social\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Json;

/**
 * Plugin implementation of the 'social_google' formatter.
 *
 * The 'Default' formatter is different for integer fields on the one hand, and
 * for decimal and float fields on the other hand, in order to be able to use
 * different settings.
 *
 * @FieldFormatter(
 *   id = "social_google",
 *   label = @Translation("Google"),
 *   field_types = {
 *     "social_google"
 *   },
 *   settings = {
 *     "count" = "0"
 *   }
 * )
 */
class SocialGoogleFormatter extends DefaultSocialFormatter {

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

    $activity_id = $this->getActivityId($url);
    $comments = $this->getComments($activity_id);
    $output = $this->renderComments($comments);

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
  public function getActivityId($url) {
    // Get URL path.
    $url = parse_url($url, PHP_URL_PATH);
    // Explode for arguments.
    $args = explode('/', $url);

    $user_id = isset($args[1]) ? $args[1] : NULL;
    $post_key = isset($args[3]) ? $args[3] : NULL;

    $cache_key = 'social_comments:' . $this->type . ':' . $this->id . ':' . $this->viewMode . ':google:' . $post_key;
    $id = FALSE;

    if ($cache = cache()->get($cache_key)) {
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

      $request = \Drupal::httpClient()->get($response_url);

      try {
        $response = $request->send();
        $data = $response->getBody(TRUE);
      }
      catch (\Exception $e) {
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = Json::decode($data);

      if (!empty($result['items'])) {
        foreach ($result['items'] as $item) {
          if (strpos($item['url'], $post_key) && strpos($item['url'], $user_id)) {
            $id = $item['id'];

            // Set data to cache.
            cache()->set($cache_key, $id, $this->expire + REQUEST_TIME);
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
  public function getComments($id) {
    $comments = array();
    $cache_key = 'social_comments:' . $this->type . ':' . $this->id . ':' . $this->viewMode . ':google:' . $id;

    if ($cache = cache()->get($cache_key)) {
      $comments = $cache->data;
    }
    else {
      $query = array(
        'key' => $this->api_key,
        'maxResults' => !empty($this->max_items) ? $this->max_items : NULL,
      );
      $query = array_filter($query);

      $response_url = url(
        'https://www.googleapis.com/plus/v1/activities/' . $id . '/comments',
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
        drupal_set_message(t('Google comments error'), 'warning');
        watchdog_exception('social_comments', $e, $e->getMessage(), array(), WATCHDOG_WARNING);
        return FALSE;
      }

      $result = Json::decode($data);

      if (!empty($result['items'])) {
        $comments = $result['items'];
        // Set data to cache.
        cache()->set($cache_key, $comments, $this->expire + REQUEST_TIME);
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
  public function renderComments($items) {
    $comments = array();

    if (is_array($items)) {
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

    return theme('social_items', array('comments' => $comments));
  }
}
