<?php

namespace Drupal\address\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Builds the form to delete an address format.
 */
class AddressFormatDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the address format for %country?', [
      '%country' => $this->getEntity()->label(),
    ]);
  }

}
