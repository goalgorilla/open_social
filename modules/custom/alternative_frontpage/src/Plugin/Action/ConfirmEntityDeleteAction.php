<?php

namespace Drupal\alternative_frontpage\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\views_bulk_operations\Plugin\Action\EntityDeleteAction;

/**
 * Add confirmation to entity delete action.
 *
 * Triggered by AlternativeFrontpageEntityDeleteValidation::infoAlter function.
 */
class ConfirmEntityDeleteAction extends EntityDeleteAction implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['#title'] = $this->t('You are going to Delete selected entities / translations');

    return $form;
  }

}
