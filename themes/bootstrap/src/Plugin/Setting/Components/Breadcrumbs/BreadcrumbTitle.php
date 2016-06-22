<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\Components\Breadcrumbs\BreadcrumbTitle.
 */

namespace Drupal\bootstrap\Plugin\Setting\Components\Breadcrumbs;

use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Plugin\Setting\SettingBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "breadcrumb_title" theme setting.
 *
 * @BootstrapSetting(
 *   id = "breadcrumb_title",
 *   type = "checkbox",
 *   title = @Translation("Show current page title at end"),
 *   description = @Translation("If your site has a module dedicated to handling breadcrumbs already, ensure this setting is disabled."),
 *   defaultValue = 1,
 *   groups = {
 *     "components" = @Translation("Components"),
 *     "breadcrumbs" = @Translation("Breadcrumbs"),
 *   },
 * )
 */
class BreadcrumbTitle extends SettingBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    $element = $this->getElement($form, $form_state);
    $element->setProperty('states', [
      'invisible' => [
        ':input[name="breadcrumb"]' => ['value' => 0],
      ],
    ]);
  }

}
