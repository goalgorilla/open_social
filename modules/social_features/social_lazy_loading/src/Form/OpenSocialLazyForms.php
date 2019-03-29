<?php

namespace Drupal\lazy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Lazy settings for this site.
 */
class LazyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lazy_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lazy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('lazy.settings');
    $filter_enabled = lazy_is_enabled();
    $description = $this->t('The inline images and iframes will be lazy-loaded for the selected text-formats');
    $desc_extra = $this->t('The %filter filter must be enabled for at least one <a href=":path">text-format</a>.', [
      ':path' => Url::fromRoute('filter.admin_overview')->toString(),
      '%filter' => 'Lazy-load',
    ]);

    if (
      !(bool) $config->get('alter_tag')['img']
      && !(bool) $config->get('alter_tag')['iframe']
    ) {
      $this->messenger()->addStatus($this->t('Lazy-load is currently disabled. Update configuration in global settings to enable it.'), 'warning');
    }

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global settings'),
      '#open' => TRUE,
    ];

    $form['settings']['alter_tag'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the inline elements to be lazy-loaded via filter.'),
      '#options' => [
        'img' => $this->t('Enable for images (%img tags)', ['%img' => '<img>']),
        'iframe' => $this->t('Enable for iframes (%iframe tags)', ['%iframe' => '<iframe>']),
      ],
      '#default_value' => $config->get('alter_tag'),
      '#description' => $description . ($filter_enabled ? '' : $desc_extra),
      '#disabled' => !$filter_enabled,
    ];

    $form['settings']['image_fields'] = [
      '#type' => 'checkbox',
      '#title' => '<del>' . $this->t('Enable on image fields attached to fieldable entities. For example, content-types, blocks, paragraphs.') . '</del>',
      '#description' => $this->t('This option is now controlled separately for each image field, in their display settings.'),
      '#default_value' => 0,
      '#return_value' => TRUE,
      '#disabled' => TRUE,
    ];

    $form['settings']['placeholderSrc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder image URL'),
      '#default_value' => $config->get('placeholderSrc'),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['paths'] = array(
      '#type' => 'details',
      '#title' => $this->t('Disabled pages'),
      '#description' => $this->t('Lazy-loading is disabled for both <em>image fields</em> and <em>inline images/iframes</em> on following pages.'),
      '#open' => FALSE,
    );

    $form['paths']['disabled_paths'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => $config->get('disabled_paths'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
    );

    $form['blazy'] = [
      '#type' => 'details',
      '#title' => $this->t('bLazy configuration'),
      '#description' => $this->t('<p><a href=":url">bLazy</a> is a lightweight lazy loading and multi-serving image script created by Bjoern Klinggaard. See its website for usage details and demos.</p>', [
        ':url' => 'http://dinbror.dk/blog/blazy/',
      ]),
      '#open' => FALSE,
    ];

    $form['blazy']['loadInvisible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('loadInvisible'),
      '#description' => $this->t('If checked loads invisible (hidden) elements.'),
      '#default_value' => $config->get('loadInvisible'),
      '#return_value' => TRUE,
    ];

    $form['blazy']['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('offset'),
      '#description' => $this->t('The offset controls how early you want the elements to be loaded before they’re visible.'),
      '#default_value' => $config->get('offset'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['blazy']['saveViewportOffsetDelay'] = [
      '#type' => 'number',
      '#title' => $this->t('saveViewportOffsetDelay'),
      '#description' => $this->t('Delay for how often it should call the saveViewportOffset function on resize.'),
      '#default_value' => $config->get('saveViewportOffsetDelay'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['blazy']['validateDelay'] = [
      '#type' => 'number',
      '#title' => $this->t('validateDelay'),
      '#description' => $this->t('Delay for how often it should call the validate function on scroll/resize.'),
      '#default_value' => $config->get('validateDelay'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['blazy']['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector class'),
      '#description' => $this->t('Element selector for elements that should lazy load. Do not include a leading period.'),
      '#default_value' => $config->get('selector'),
      '#required' => TRUE,
    ];

    $form['blazy']['skipClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('skipClass'),
      '#description' => $this->t('Elements having this class name will be ignored.'),
      '#default_value' => $config->get('skipClass'),
      '#required' => TRUE,
    ];

    $form['blazy']['errorClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('errorClass'),
      '#description' => $this->t('The classname an element will get if something goes wrong.'),
      '#default_value' => $config->get('errorClass'),
      '#required' => TRUE,
    ];

    $form['blazy']['successClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('successClass'),
      '#description' => $this->t('The classname an element will get when loaded.'),
      '#default_value' => $config->get('successClass'),
      '#required' => TRUE,
    ];

    $form['blazy']['src'] = [
      '#type' => 'textfield',
      '#title' => $this->t('src'),
      '#description' => $this->t('Attribute where the original element source will be assigned. Do not change this unless attribute is used for other purposes.'),
      '#default_value' => $config->get('src'),
      '#required' => TRUE,
    ];

    $form['clear_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check the box to clear the cache'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('lazy.settings')
      ->set('alter_tag', $form_state->getValue('alter_tag'))
      ->set('disabled_paths', $form_state->getValue('disabled_paths'))
      ->set('errorClass', $form_state->getValue('errorClass'))
      ->set('loadInvisible', (bool) $form_state->getValue('loadInvisible'))
      ->set('offset', (int) $form_state->getValue('offset'))
      ->set('saveViewportOffsetDelay', (int) $form_state->getValue('saveViewportOffsetDelay'))
      ->set('selector', $form_state->getValue('selector'))
      ->set('skipClass', $form_state->getValue('skipClass'))
      ->set('src', $form_state->getValue('src'))
      ->set('successClass', $form_state->getValue('successClass'))
      ->set('validateDelay', $form_state->getValue('validateDelay'))
      ->set('placeholderSrc', $form_state->getValue('placeholderSrc'))
      ->save();
    parent::submitForm($form, $form_state);

    if ($form_state->getValue('clear_cache')) {
      $this->cacheClear();
    }
  }

  /**
   * Clears all caches, then redirects to the previous page.
   */
  public function cacheClear() {
    drupal_flush_all_caches();
    $this->messenger()->addMessage('Cache cleared.');
    // return $this->redirect('lazy.config_form');
  }

}