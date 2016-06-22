<?php

/**
 * @file
 * Contains \Drupal\profile\Form\ProfileDeleteForm.
 */

namespace Drupal\profile\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for deleting a profile entity.
 */
class ProfileDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.user.canonical', [
      'user' => $this->entity->getOwnerId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
