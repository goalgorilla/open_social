<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Modals\ModalAnimation.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Modals;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "modal_animation" theme setting.
 *
 * @BootstrapSetting(
 *   id = "modal_animation",
 *   type = "checkbox",
 *   title = @Translation("animation"),
 *   description = @Translation("Apply a CSS fade transition to modals."),
 *   defaultValue = 1,
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "modals" = @Translation("Modals"),
 *     "options" = @Translation("Options"),
 *   },
 * )
 */
class ModalAnimation extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterForm($form, $form_state, $form_id);
    $group = $this->getGroup($form, $form_state);
    $group->setProperty('description', t('These are global options. Each modal can independently override desired settings by appending the option name to <code>data-</code>. Example: <code>data-backdrop="false"</code>.'));
    $group->setProperty('states', [
      'visible' => [
        ':input[name="modal_enabled"]' => ['checked' => TRUE],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return !!$this->theme->getSetting('modal_enabled');
  }

}
