<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\BootstrapModal.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Component\Utility\Html;

/**
 * Pre-processes variables for the "bootstrap_modal" theme hook.
 *
 * @ingroup theme_preprocess
 *
 * @BootstrapPreprocess("bootstrap_modal")
 */
class BootstrapModal extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  protected function preprocessVariables(Variables $variables, $hook, array $info) {
    // Immediately log an error and return if Bootstrap modals are not enabled.
    if (!$this->theme->getSetting('modal_enabled')) {
      \Drupal::logger('bootstrap')->error(t('Bootstrap modals are not enabled.'));
      return;
    }

    // Retrieve the ID, generating one if needed.
    $id = $variables->getAttribute('id', Html::getUniqueId($variables->offsetGet('id', 'bootstrap-modal')));
    $variables->setAttribute('id', $id);
    unset($variables['id']);

    if ($variables->title) {
      $title_id = $variables->getAttribute('id', "$id--title", $variables::TITLE);
      $variables->setAttribute('id', $title_id, $variables::TITLE);
      $variables->setAttribute('aria-labelledby', $title_id);
    }

    // Use a provided modal size or retrieve the default theme setting.
    $variables->size = $variables->size ?: $this->theme->getSetting('modal_size');

    // Convert the description variable.
    $this->preprocessDescription($variables, $hook, $info);

    // Ensure all attributes are proper objects.
    $this->preprocessAttributes($variables, $hook, $info);
  }

}
