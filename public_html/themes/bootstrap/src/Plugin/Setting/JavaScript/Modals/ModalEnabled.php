<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Modals\ModalEnabled.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Modals;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "modal_enabled" theme setting.
 *
 * @BootstrapSetting(
 *   id = "modal_enabled",
 *   type = "checkbox",
 *   title = @Translation("Enable Bootstrap Modals"),
 *   description = @Translation("Enabling this will replace core's jQuery UI Dialog implementations with modals from the Bootstrap Framework."),
 *   defaultValue = 1,
 *   weight = -1,
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "modals" = @Translation("Modals"),
 *   },
 * )
 */
class ModalEnabled extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterForm($form, $form_state, $form_id);
    $group = $this->getGroup($form, $form_state);
    $group->setProperty('description', t('Modals are streamlined, but flexible, dialog prompts with the minimum required functionality and smart defaults. See <a href=":url" target="_blank">Bootstrap Modals</a> for more documentation.', [
      ':url' => 'http://getbootstrap.com/javascript/#modals',
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['rendered', 'library_info'];
  }

}
