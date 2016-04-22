<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\UncacheableDependencyTrait;

/**
 * Defines a contextual filter for doing fulltext searches.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_fulltext")
 */
class SearchApiFulltext extends SearchApiStandard {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['fields'] = array('default' => array());
    $options['conjunction'] = array('default' => 'AND');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['help']['#markup'] = Html::escape($this->t('Note: You can change how search keys are parsed under "Advanced" > "Query settings".'));

    $fields = $this->getFulltextFields();
    if (!empty($fields)) {
      $form['fields'] = array(
        '#type' => 'select',
        '#title' => $this->t('Searched fields'),
        '#description' => $this->t('Select the fields that will be searched. If no fields are selected, all available fulltext fields will be searched.'),
        '#options' => $fields,
        '#size' => min(4, count($fields)),
        '#multiple' => TRUE,
        '#default_value' => $this->options['fields'],
      );
      $form['conjunction'] = array(
        '#title' => $this->t('Operator'),
        '#description' => $this->t('Determines how multiple keywords entered for the search will be combined.'),
        '#type' => 'radios',
        '#options' => array(
          'AND' => $this->t('Contains all of these words'),
          'OR' => $this->t('Contains any of these words'),
        ),
        '#default_value' => $this->options['conjunction'],
      );
    }
    else {
      $form['fields'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    if ($this->options['fields']) {
      $this->query->setFulltextFields($this->options['fields']);
    }
    if ($this->options['conjunction'] != 'AND') {
      $this->query->setOption('conjunction', $this->options['conjunction']);
    }

    $old = $this->query->getOriginalKeys();
    $this->query->keys($this->argument);
    if ($old) {
      $keys = &$this->query->getKeys();
      if (is_array($keys)) {
        $keys[] = $old;
      }
      elseif (is_array($old)) {
        // We don't support such nonsense.
      }
      else {
        $keys = "($old) ($keys)";
      }
    }
  }

  /**
   * Retrieves an options list of available fulltext fields.
   *
   * @return string[]
   *   An associative array mapping the identifiers of the index's fulltext
   *   fields to their prefixed labels.
   */
  protected function getFulltextFields() {
    $fields = array();

    if (!empty($this->query)) {
      $index = $this->query->getIndex();
    }
    else {
      $index = SearchApiQuery::getIndexFromTable($this->table);
    }

    if (!$index) {
      return array();
    }

    $fields_info = $index->getFields();
    foreach ($index->getFulltextFields() as $field_id) {
      $fields[$field_id] = $fields_info[$field_id]->getPrefixedLabel();
    }

    return $fields;
  }

}
