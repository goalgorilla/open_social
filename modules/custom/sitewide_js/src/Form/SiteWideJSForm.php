<?php

namespace Drupal\sitewide_js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SiteWideJSForm.
 *
 * @package Drupal\sitewide_js\Form
 */
class SiteWideJSForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sitewide_js.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sitewide_js_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sitewide_js.settings');

    $form['warning']['#markup'] = $this->t('<h2>Warning</h2><p>Be careful, in this section you can make sitewide changes to the HTML template and therefore break both the front-end javascript applications and the layout for your website.</p>');

    $form['swjs_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable site wide JS'),
      '#description' => $this->t('Turn sitewide JS on or off.'),
      '#default_value' => $config->get('swjs_enabled'),
    );

    $form['swjs_location'] = array(
      '#type' => 'radios',
      '#title' => $this->t('JS Location'),
      '#description' => $this->t('The output location of the sitewide JS.'),
      '#default_value' => $config->get('swjs_location'),
      '#options' => array(0 => $this->t('Header'), 1 => $this->t('Footer')),
    );

    $form['swjs_footer_region'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Footer region'),
      '#description' => $this->t('Footer region to put the JS in.'),
      '#default_value' => $config->get('swjs_footer_region'),
      '#states' => array(
        'visible' => array(
          ':input[name="swjs_location"]' => array('value' => '1'),
        ),
      ),
    );

    $form['swjs_javascript'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Javascript'),
      '#description' => $this->t('The JS to add to the site.'),
      '#default_value' => $config->get('swjs_javascript'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sitewide_js.settings')
      ->set('swjs_enabled', $form_state->getValue('swjs_enabled'))
      ->set('swjs_location', $form_state->getValue('swjs_location'))
      ->set('swjs_footer_region', $form_state->getValue('swjs_footer_region'))
      ->set('swjs_javascript', $form_state->getValue('swjs_javascript'))
      ->save();
  }
}
