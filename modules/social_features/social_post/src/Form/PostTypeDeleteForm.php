<?php

namespace Drupal\social_post\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Post type entities.
 */
class PostTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('entity.post_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->entity->delete();

    $this->messenger()->addStatus($this->t('content @type: deleted @label.',
      [
        '@type' => $this->entity->bundle(),
        '@label' => $this->entity->label(),
      ]
      ));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
