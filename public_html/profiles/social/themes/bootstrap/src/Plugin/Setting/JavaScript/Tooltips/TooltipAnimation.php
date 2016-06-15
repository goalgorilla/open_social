<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips\TooltipAnimation.
 */

namespace Drupal\bootstrap\Plugin\Setting\JavaScript\Tooltips;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "tooltip_animation" theme setting.
 *
 * @BootstrapSetting(
 *   id = "tooltip_animation",
 *   type = "checkbox",
 *   title = @Translation("animation"),
 *   description = @Translation("Apply a CSS fade transition to the tooltip."),
 *   defaultValue = 1,
 *   groups = {
 *     "javascript" = @Translation("JavaScript"),
 *     "tooltips" = @Translation("Tooltips"),
 *     "options" = @Translation("Options"),
 *   },
 * )
 */
class TooltipAnimation extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterForm($form, $form_state, $form_id);

    $group = $this->getGroup($form, $form_state);
    $group->setProperty('description', t('These are global options. Each tooltip can independently override desired settings by appending the option name to <code>data-</code>. Example: <code>data-animation="false"</code>.'));
    $group->setProperty('states', [
      'visible' => [
        ':input[name="tooltip_enabled"]' => ['checked' => TRUE],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    return !!$this->theme->getSetting('tooltip_enabled');
  }

}
