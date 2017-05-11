<?php

namespace Drupal\social_post\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Post entities.
 *
 * @ingroup social_post
 */
class PostDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Set the redirect url of the cancel button.
    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::destination();
      $path = $destination->get();
      $form['actions']['cancel']['#url'] = Url::fromUserInput($path);
    }
    else {
      $curr_route_parr = \Drupal::routeMatch()->getRawParameters()->getDigits('post');
      $redirect_url = Url::fromRoute('entity.post.edit_form', ['post' => $curr_route_parr]);
      $form['actions']['cancel']['#url'] = $redirect_url;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    // Set the redirect url to the destination in the url.
    if (\Drupal::request()->query->has('destination')) {
      $destination = \Drupal::destination();
      $path = $destination->get();
      return Url::fromUserInput($path);
    }
    // Default to the stream page.
    return Url::fromRoute('social_core.homepage');
  }

}
