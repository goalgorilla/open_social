<?php

/**
 * @file
 * Contains \Drupal\field_group\Plugin\field_group\FieldGroupFormatter\HorizontalTabs.
 */

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'horizontal_tabs' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "tabs",
 *   label = @Translation("Tabs"),
 *   description = @Translation("This fieldgroup renders child groups in its own tabs wrapper."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class Tabs extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $element += array(
      '#prefix' => '<div class=" ' . implode(' ' , $this->getClasses()) . '">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#parents' => array($this->group->group_name),
      '#default_tab' => '',
    );

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    // By default tabs don't have titles but you can override it in the theme.
    if ($this->getLabel()) {
      $element['#title'] = SafeMarkup::checkPlain($this->getLabel());
    }

    $form_state = new \Drupal\Core\Form\FormState();

    if ($this->getSetting('direction') == 'vertical') {
      $element += array(
        '#type' => 'vertical_tabs',
        '#theme_wrappers' => array('vertical_tabs'),
      );
      $complete_form = array();
      $element = \Drupal\Core\Render\Element\VerticalTabs::processVerticalTabs($element, $form_state, $complete_form);
    }
    else {
      $element += array(
        '#type' => 'horizontal_tabs',
        '#theme_wrappers' => array('horizontal_tabs'),
      );
      $on_form = $this->context == 'form';
      $element = \Drupal\field_group\Element\HorizontalTabs::processHorizontalTabs($element, $form_state, $on_form);
    }

    // Make sure the group has 1 child. This is needed to succeed at form_pre_render_vertical_tabs().
    // Skipping this would force us to move all child groups to this array, making it an un-nestable.
    $element['group']['#groups'][$this->group->group_name] = array(0 => array());
    $element['group']['#groups'][$this->group->group_name]['#group_exists'] = TRUE;

    // Search for a tab that was marked as open. First one wins.
    foreach (\Drupal\Core\Render\Element::children($element) as $tab_name) {
      if (!empty($element[$tab_name]['#open'])) {
        $element[$this->group->group_name . '__active_tab']['#default_value'] = $tab_name;
        break;
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['direction'] = array(
      '#title' => $this->t('Direction'),
      '#type' => 'select',
      '#options' => array(
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ),
      '#default_value' => $this->getSetting('direction'),
      '#weight' => 1,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();
    $summary[] = $this->t('Direction: @direction',
      array('@direction' => $this->getSetting('direction'))
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return array(
      'direction' => 'vertical',
    ) + parent::defaultContextSettings($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getClasses() {

    $classes = parent::getClasses();
    $classes[] = 'field-group-' . $this->group->format_type . '-wrapper';

    return $classes;
  }

}
