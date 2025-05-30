<?php

namespace Drupal\social_profile\Event;

use Drupal\profile\Entity\ProfileInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;

/**
 * Event dispatched before the profile entity is saved via form submission.
 *
 * Allows subscribers to react to the profile form submission prior to the
 * entity's presave processing.
 */
class SocialProfilePrePresaveSubmitEvent extends Event {

  /**
   * The name of the event to dispatch.
   */
  public const EVENT_NAME = 'social_profile.social_profile_pre_presave_form_submit';

  /**
   * The profile entity associated with the form.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected ProfileInterface $profile;

  /**
   * The form array being submitted.
   *
   * @var array
   */
  protected array $form;

  /**
   * The current state of the form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected FormStateInterface $formState;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile entity associated with the submitted form.
   * @param array &$form
   *   The form array being submitted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function __construct(ProfileInterface $profile, array &$form, FormStateInterface $form_state) {
    $this->profile = $profile;
    $this->form = $form;
    $this->formState = $form_state;
  }

  /**
   * Gets the profile entity associated with the event.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The profile entity.
   */
  public function getProfile(): ProfileInterface {
    return $this->profile;
  }

  /**
   * Gets the form array associated with the event.
   *
   * @return array
   *   The form array.
   */
  public function getForm(): array {
    return $this->form;
  }

  /**
   * Gets the current form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state object.
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

}
