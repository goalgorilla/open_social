<?php

/**
 * @file
 * Contains \Drupal\field_group\Plugin\field_group\FieldGroupFormatter\HtmlElement.
 */

namespace Drupal\field_group\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormState;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'html_element' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "html_element",
 *   label = @Translation("Html element"),
 *   description = @Translation("This fieldgroup renders the inner content in a HTML element with classes and attributes."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class HtmlElement extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $element_attributes = new Attribute();

    if ($this->getSetting('attributes')) {

      // This regex split the attributes string so that we can pass that
      // later to drupal_attributes().
      preg_match_all('/([^\s=]+)="([^"]+)"/', $this->getSetting('attributes'), $matches);

      // Put the attribute and the value together.
      foreach ($matches[1] as $key => $attribute) {
        $element_attributes[$attribute] = $matches[2][$key];
      }

    }

    // Add the id to the attributes array.
    if ($this->getSetting('id')) {
      $element_attributes['id'] = Html::getId($this->getSetting('id'));
    }

    // Add the classes to the attributes array.
    $classes = $this->getClasses();
    if (!empty($classes)) {
      if (!isset($element_attributes['class'])) {
        $element_attributes['class'] = array();
      }
      // If user also entered class in the attributes textfield, force it to an array.
      else {
        $element_attributes['class'] = array($element_attributes['class']);
      }
      $element_attributes['class'] = array_merge($classes, $element_attributes['class']->value());
    }

    $element['#effect'] = $this->getSetting('effect');
    $element['#speed'] = $this->getSetting('speed');
    $element['#type'] = 'field_group_html_element';
    $element['#wrapper_element'] = $this->getSetting('element');
    $element['#attributes'] = $element_attributes;
    if ($this->getSetting('show_label')) {
      $element['#title_element'] = $this->getSetting('label_element');
      $element['#title'] = SafeMarkup::checkPlain($this->t($this->getLabel()));
    }

    $form_state = new FormState();
    \Drupal\field_group\Element\HtmlElement::processHtmlElement($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $form['element'] = array(
      '#title' => $this->t('Element'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('element'),
      '#description' => $this->t('E.g. div, section, aside etc.'),
      '#weight' => 1,
    );

    $form['show_label'] = array(
      '#title' => $this->t('Show label'),
      '#type' => 'select',
      '#options' => array(0 => $this->t('No'), 1 => $this->t('Yes')),
      '#default_value' => $this->getSetting('show_label'),
      '#weight' => 2,
    );

    $form['label_element'] = array(
      '#title' => $this->t('Label element'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('label_element'),
      '#weight' => 3,
    );

    $form['attributes'] = array(
      '#title' => $this->t('Attributes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('attributes'),
      '#description' => $this->t('E.g. name="anchor"'),
      '#weight' => 4,
    );

    $form['effect'] = array(
      '#title' => $this->t('Effect'),
      '#type' => 'select',
      '#options' => array(
        'none' => $this->t('None'),
        'collapsible' => $this->t('Collapsible'),
        'blind' => $this->t('Blind')
      ),
      '#default_value' => $this->getSetting('effect'),
      '#weight' => 5,
    );

    $form['speed'] = array(
      '#title' => $this->t('Speed'),
      '#type' => 'select',
      '#options' => array('slow' => $this->t('Slow'), 'fast' => $this->t('Fast')),
      '#default_value' => $this->getSetting('speed'),
      '#weight' => 6,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();
    $summary[] = $this->t('Element: @element',
      array('@element' => $this->getSetting('element'))
    );

    if ($this->getSetting('show_label')) {
      $summary[] = $this->t('Label element: @element',
        array('@element' => $this->getSetting('label_element'))
      );
    }

    if ($this->getSetting('attributes')) {
      $summary[] = $this->t('Attributes: @attributes',
        array('@attributes' => $this->getSetting('attributes'))
      );
    }

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = array(
      'element' => 'div',
      'show_label' => 0,
      'label_element' => 'h3',
      'effect' => 'none',
      'speed' => 'fast',
      'attributes' => '',
    ) + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;

  }

}
