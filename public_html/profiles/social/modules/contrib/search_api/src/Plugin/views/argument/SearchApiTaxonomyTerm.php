<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Component\Utility\Html;
use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines a contextual filter searching through all indexed taxonomy fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_taxonomy_term")
 */
// @todo This seems to be only partially ported to D8.
class SearchApiTaxonomyTerm extends SearchApiStandard {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->fillValue();

    $outer_conjunction = strtoupper($this->operator);

    if (empty($this->options['not'])) {
      $condition_operator = '=';
      $inner_conjunction = 'OR';
    }
    else {
      $condition_operator = '<>';
      $inner_conjunction = 'AND';
    }

    if (!empty($this->value)) {
      $terms = Term::load($this->value);

      if (!empty($terms)) {
        $conditions = $this->query->createConditionGroup($outer_conjunction);
        $vocabulary_fields = $this->definition['vocabulary_fields'];
        $vocabulary_fields += array('' => array());
        foreach ($terms as $term) {
          $inner_conditions = $conditions;
          if ($outer_conjunction != $inner_conjunction) {
            $inner_conditions = $this->query->createConditionGroup($inner_conjunction);
          }
          // Set filters for all term reference fields which don't specify a
          // vocabulary, as well as for all fields specifying the term's
          // vocabulary.
          if (!empty($vocabulary_fields[$term->vocabulary_id])) {
            foreach ($vocabulary_fields[$term->vocabulary_id] as $field) {
              $inner_conditions->addCondition($field, $term->tid, $condition_operator);
            }
          }
          foreach ($vocabulary_fields[''] as $field) {
            $inner_conditions->addCondition($field, $term->tid, $condition_operator);
          }
          if ($outer_conjunction != $inner_conjunction) {
            $conditions->addConditionGroup($inner_conditions);
          }
        }

        $this->query->addConditionGroup($conditions);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    if (!empty($this->argument)) {
      $this->fillValue();
      $terms = array();
      foreach ($this->value as $tid) {
        $taxonomy_term = Term::load($tid);
        if ($taxonomy_term) {
          $terms[] = Html::escape($taxonomy_term->label());
        }
      }

      return $terms ? implode(', ', $terms) : Html::escape($this->argument);
    }
    else {
      return Html::escape($this->argument);
    }
  }

}
