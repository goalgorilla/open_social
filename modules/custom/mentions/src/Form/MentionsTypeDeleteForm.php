<?php

/**
 * @file
 * Contains \Drupal\mentions\Form\MentionsTypeDeleteForm.
 */

namespace Drupal\mentions\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for the mentions type deletion form.
 */
class MentionsTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.mentions_type.list');
  }

}
