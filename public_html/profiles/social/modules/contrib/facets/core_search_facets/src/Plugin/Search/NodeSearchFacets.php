<?php

namespace Drupal\core_search_facets\Plugin\Search;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Plugin\Search\NodeSearch;

/**
 * Handles searching for node entities using the Search module index.
 */
class NodeSearchFacets extends NodeSearch {

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getParameters();
    $keys = $this->getKeywords();
    $used_advanced = !empty($parameters[self::ADVANCED_FORM]);
    if ($used_advanced) {
      $f = isset($parameters['f']) ? (array) $parameters['f'] : array();
      $defaults = $this->parseAdvancedDefaults($f, $keys);
    }
    else {
      $defaults = array('keys' => $keys);
    }

    $form['basic']['keys']['#default_value'] = $defaults['keys'];

    // Add advanced search keyword-related boxes.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced search'),
      '#attributes' => array('class' => array('search-advanced')),
      '#access' => $this->account && $this->account->hasPermission('use advanced search'),
      '#open' => $used_advanced,
    );

    $form['advanced']['keywords-fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Keywords'),
    );

    $form['advanced']['keywords'] = array(
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
    );

    $form['advanced']['keywords-fieldset']['keywords']['or'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing any of the words'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => isset($defaults['or']) ? $defaults['or'] : '',
    );

    $form['advanced']['keywords-fieldset']['keywords']['phrase'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing the phrase'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => isset($defaults['phrase']) ? $defaults['phrase'] : '',
    );

    $form['advanced']['keywords-fieldset']['keywords']['negative'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing none of the words'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => isset($defaults['negative']) ? $defaults['negative'] : '',
    );

    $form['advanced']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Advanced search'),
      '#prefix' => '<div class="action">',
      '#suffix' => '</div>',
      '#weight' => 100,
    );

    if (\Drupal::config("facets.facet_source.core_node_search__{$this->searchPageId}")->get('third_party_settings.core_search_facets.advanced_filters')) {
      // Add node types.
      $types = array_map(array('\Drupal\Component\Utility\Html', 'escape'), node_type_get_names());
      $form['advanced']['types-fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Types'),
      );
      $form['advanced']['types-fieldset']['type'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Only of the type(s)'),
        '#prefix' => '<div class="criterion">',
        '#suffix' => '</div>',
        '#options' => $types,
        '#default_value' => isset($defaults['type']) ? $defaults['type'] : array(),
      );

      $form['advanced']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Advanced search'),
        '#prefix' => '<div class="action">',
        '#suffix' => '</div>',
        '#weight' => 100,
      );

      // Add languages.
      $language_options = array();
      $language_list = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
      foreach ($language_list as $langcode => $language) {
        // Make locked languages appear special in the list.
        $language_options[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->getName())) : $language->getName();
      }
      if (count($language_options) > 1) {
        $form['advanced']['lang-fieldset'] = array(
          '#type' => 'fieldset',
          '#title' => t('Languages'),
        );
        $form['advanced']['lang-fieldset']['language'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Languages'),
          '#prefix' => '<div class="criterion">',
          '#suffix' => '</div>',
          '#options' => $language_options,
          '#default_value' => isset($defaults['language']) ? $defaults['language'] : array(),
        );
      }
    }
  }

}
