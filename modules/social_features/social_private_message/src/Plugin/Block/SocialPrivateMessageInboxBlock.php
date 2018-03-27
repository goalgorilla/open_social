<?php

namespace Drupal\social_private_message\Plugin\Block;

use Drupal\Core\Url;
use Drupal\private_message\Plugin\Block\PrivateMessageInboxBlock;

/**
 * Provides a 'SocialPrivateMessageInboxBlock' block.
 *
 * @Block(
 *   id = "social_private_message_inbox_block",
 *   admin_label = @Translation("Social Private Message Inbox"),
 * )
 */
class SocialPrivateMessageInboxBlock extends PrivateMessageInboxBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->currentUser->isAuthenticated() && $this->currentUser->hasPermission('use private messaging system')) {
      $config = $this->getConfiguration();
      $thread_info = $this->privateMessageService->getThreadsForUser($config['thread_count']);

      if (count($thread_info['threads'])) {

        $view_builder = $this->entityManager->getViewBuilder('private_message_thread');
        $threads = $thread_info['threads'];

        /* @var \Drupal\private_message\Entity\PrivateMessageThread $thread */
        // This custom sort, sorts based on newestmessage timestamp in the
        // thread.
        uasort($threads, [$this, "customSort"]);
        // The above sorts ascending... so:
        $threads = array_reverse($threads);

        foreach ($threads as $thread) {
          $block[$thread->id()] = $view_builder->view($thread, 'inbox');
        }
      }
      else {
        $block['no_threads'] = [
          '#prefix' => '<p>',
          '#suffix' => '</p>',
          '#markup' => $this->t('You do not have any private messages yet. Click on the button on the right to start a new chat.'),
        ];
      }

      $new_url = Url::fromRoute('private_message.ajax_callback', ['op' => 'get_new_inbox_threads']);
      $new_token = $this->csrfToken->get($new_url->getInternalPath());
      $new_url->setOptions(['absolute' => TRUE, 'query' => ['token' => $new_token]]);

      // Add the default classes, as these are not added when the block
      // output is overridden with a template.
      $block['#attributes']['class'][] = 'block';
      $block['#attributes']['class'][] = 'block-private-message';
      $block['#attributes']['class'][] = 'block-private-message-inbox-block';

      return $block;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $cache_contexts[] = 'user';
    return $cache_contexts;
  }

  /**
   * Sorts the inbox based on last updated time.
   */
  public function customSort($pmt1, $pmt2) {

    /* @var \Drupal\private_message\Entity\PrivateMessageThread $pmt1 */
    /* @var \Drupal\private_message\Entity\PrivateMessageThread $pmt2 */
    if ($pmt1->getUpdatedTime() == $pmt2->getUpdatedTime()) {
      return 0;
    }
    return ($pmt1->getUpdatedTime() < $pmt2->getUpdatedTime()) ? -1 : 1;
  }

}
