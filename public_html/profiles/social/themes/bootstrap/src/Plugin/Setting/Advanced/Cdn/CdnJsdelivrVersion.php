<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Setting\Advanced\Cdn\CdnJsdelivrVersion.
 */

namespace Drupal\bootstrap\Plugin\Setting\Advanced\Cdn;

use Drupal\bootstrap\Annotation\BootstrapConstant;
use Drupal\bootstrap\Annotation\BootstrapSetting;
use Drupal\bootstrap\Bootstrap;
use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;

/**
 * The "cdn_jsdelivr_version" theme setting.
 *
 * @BootstrapSetting(
 *   cdn_provider = "jsdelivr",
 *   id = "cdn_jsdelivr_version",
 *   type = "select",
 *   weight = -1,
 *   title = @Translation("Version"),
 *   defaultValue = @BootstrapConstant("Drupal\bootstrap\Bootstrap::FRAMEWORK_VERSION"),
 *   groups = {
 *     "advanced" = @Translation("Advanced"),
 *     "cdn" = @Translation("CDN (Content Delivery Network)"),
 *     "jsdelivr" = false,
 *   },
 * )
 */
class CdnJsdelivrVersion extends CdnProvider {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, $form_id = NULL) {
    $plugin_id = Html::cleanCssIdentifier($this->provider->getPluginId());
    $setting = $this->getElement($form, $form_state);

    $setting->setProperty('options', $this->provider->getVersions());
    $setting->setProperty('ajax', [
      'callback' => [get_class($this), 'ajaxCallback'],
      'wrapper' => 'cdn-provider-' . $plugin_id,
    ]);

    if (!$this->provider->hasError() && !$this->provider->isImported()) {
      $setting->setProperty('description', t('These versions are automatically populated by the @provider API upon cache clear and newer versions may appear over time. It is highly recommended the version that the site was built with stays at that version. Until a newer version has been properly tested for updatability by the site maintainer, you should not arbitrarily "update" just because there is a newer version. This can cause many inconsistencies and undesired effects with an existing site.', [
        '@provider' => $this->provider->getLabel(),
      ]));
    }
  }

  /**
   * AJAX callback for reloading CDN provider elements.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['advanced']['cdn'][$form_state->getValue('cdn_provider', Bootstrap::getTheme()->getSetting('cdn_provider'))];
  }

}
