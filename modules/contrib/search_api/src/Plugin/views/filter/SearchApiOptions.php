<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\filter\ManyToOne;

/**
 * Defines a filter for filtering on fields with a fixed set of possible values.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_options")
 */
class SearchApiOptions extends ManyToOne {

  use UncacheableDependencyTrait;
  use SearchApiFilterTrait;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    unset($form['reduce_duplicates']);
  }

}
