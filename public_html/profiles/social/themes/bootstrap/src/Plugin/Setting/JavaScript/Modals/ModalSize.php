<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Modals\ModalSize.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Modals;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;

/**
 * The "modal_size" theme setting.
 *
 * @BootstrapSetting(
 *   id = "modal_size",
 *   type = "select",
 *   title = @Translation("Default modal size"),
 *   defaultValue = "",
 *   empty_option = @Translation("Normal"),
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "modals" = @Translation("Modals"),
 *     "options" = @Translation("Options"),
 *   },
 *   options = {
 *     "modal-sm" = @Translation("Small"),
 *     "modal-lg" = @Translation("Large"),
 *   },
 * )
 */
class ModalSize extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return !!$this->theme->getSetting('modal_enabled');
  }

}
