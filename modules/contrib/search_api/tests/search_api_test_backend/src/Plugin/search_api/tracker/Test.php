<?php

/**
 * @file
 * Contains \Drupal\search_api_test_backend\Plugin\search_api\tracker\Test.
 */

namespace Drupal\search_api_test_backend\Plugin\search_api\tracker;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\search_api\tracker\Basic;

/**
 * Provides a tracker implementation which uses a FIFO-like processing order.
 *
 * @SearchApiTracker(
 *   id = "search_api_test_backend",
 *   label = @Translation("Test tracker"),
 * )
 */
class Test extends Basic {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'foo' => 'test',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return array(
      'foo' => array(
        '#type' => 'textfield',
        '#title' => 'Foo',
        '#default_value' => $this->configuration['foo'],
      ),
    );
  }

}
