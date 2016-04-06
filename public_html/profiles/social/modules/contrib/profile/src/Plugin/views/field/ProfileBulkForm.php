<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\field\NodeBulkForm.
 */

namespace Drupal\profile\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a profile operations bulk form element.
 *
 * @ViewsField("profile_bulk_form")
 */
class ProfileBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return t('No profile selected.');
  }

}
