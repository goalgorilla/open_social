<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\wizard\Node.
 */

namespace Drupal\message\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Tests creating node views with the wizard.
 *
 * @ViewsWizard(
 *   id = "message",
 *   base_table = "message",
 *   title = @Translation("Message")
 * )
 */
class Message extends WizardPluginBase {

  /**
   * Set default values for the path field options.
   */
  protected $pathField = [
    'id' => 'mid',
    'table' => 'message',
    'field' => 'mid',
    'exclude' => TRUE,
    'link_to_user' => FALSE,
    'alter' => [
      'alter_text' => TRUE,
      'text' => 'message/[mid]',
    ],
  ];

  /**
   * Set default values for the filters.
   */
  protected $filters = [
    'status' => [
      'value' => TRUE,
      'table' => 'message',
      'field' => 'status',
      'provider' => 'message',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['provider'] = 'user';

    unset($display_options['fields']);

    // Remove the default fields, since we are customizing them here.
    /* Field: Message: Text */
    $display_options['fields']['name']['id'] = 'mid';
    $display_options['fields']['name']['table'] = 'message';
    $display_options['fields']['name']['field'] = 'text';
    $display_options['fields']['name']['provider'] = 'message';
    $display_options['fields']['name']['label'] = t('Message text');
    $display_options['fields']['name']['alter']['alter_text'] = 0;
    $display_options['fields']['name']['alter']['make_link'] = 0;
    $display_options['fields']['name']['alter']['absolute'] = 0;
    $display_options['fields']['name']['alter']['trim'] = 0;
    $display_options['fields']['name']['alter']['word_boundary'] = 0;
    $display_options['fields']['name']['alter']['ellipsis'] = 0;
    $display_options['fields']['name']['alter']['strip_tags'] = 0;
    $display_options['fields']['name']['alter']['html'] = 0;
    $display_options['fields']['name']['hide_empty'] = 0;
    $display_options['fields']['name']['empty_zero'] = 0;
    $display_options['fields']['name']['link_to_taxonomy'] = 1;

    return $display_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayFilters($form, $form_state) {
    $filters = [];

    return $filters;
  }
}
