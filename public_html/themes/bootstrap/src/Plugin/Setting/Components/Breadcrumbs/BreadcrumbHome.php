<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\Components\Breadcrumbs\BreadcrumbHome.
 */

namespace Drupal\bootstrap\Plugin\Setting\Components\Breadcrumbs;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "breadcrumb_home" theme setting.
 *
 * @BootstrapSetting(
 *   id = "breadcrumb_home",
 *   type = "checkbox",
 *   title = @Translation("Show 'Home' breadcrumb link"),
 *   description = @Translation("If your site has a module dedicated to handling breadcrumbs already, ensure this setting is enabled."),
 *   defaultValue = 0,
 *   groups = {
 *     "components" = @Translation("Components"),
 *     "breadcrumbs" = @Translation("Breadcrumbs"),
 *   },
 * )
 */
class BreadcrumbHome extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    parent::alterForm($form, $form_state, $form_id);

    $element = $this->getElement($form, $form_state);
    $element->setProperty('states', [
      'invisible' => [
        ':input[name="breadcrumb"]' => ['value' => 0],
      ],
    ]);
  }

}
