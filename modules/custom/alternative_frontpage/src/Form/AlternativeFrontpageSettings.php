<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AlternativeFrontpageSettings.
 */
class AlternativeFrontpageSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'alternative_frontpage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alternative_frontpage_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alternative_frontpage.settings');
    $site_config = $this->config('system.site');
    $form['frontpage_for_anonymous_users'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage for anonymous users'),
      '#description' => $this->t('Enter the frontpage for anonymous users. This setting will override the homepage which is set in the Site Configuration form.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $site_config->get('page.front'),
    ];
    $form['frontpage_for_authenticated_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage for authenticated users'),
      '#description' => $this->t('Enter the frontpage for authenticated users. When the value is left empty it will use the anonymous homepage for authenticated users as well.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('frontpage_for_authenticated_user'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('alternative_frontpage.settings')
      ->set('frontpage_for_authenticated_user', $form_state->getValue('frontpage_for_authenticated_user'))
      ->save();

    $this->configFactory->getEditable('system.site')->set('page.front', $form_state->getValue('frontpage_for_anonymous_users'))->save();
  }

}
