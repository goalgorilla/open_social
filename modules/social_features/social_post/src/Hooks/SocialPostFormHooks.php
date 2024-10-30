<?php

namespace Drupal\social_post\Hooks;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\social_post\Service\SocialPostHelperInterface;

/**
 * Replace hook: social_post_form_post_form_alter.
 *
 * @package Drupal\social_post\Hooks
 */
final class SocialPostFormHooks {

  /**
   * The social post helper service.
   *
   * @var \Drupal\social_post\Service\SocialPostHelperInterface
   */
  private SocialPostHelperInterface $socialPostHelper;

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\social_post\Service\SocialPostHelperInterface $social_post_helper
   *   The social post helper.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The account proxy.
   */
  public function __construct(
    SocialPostHelperInterface $social_post_helper,
    AccountProxyInterface $current_user,
  ) {
    $this->socialPostHelper = $social_post_helper;
    $this->currentUser = $current_user;
  }

  /**
   * From alter hook: replacement of social_post_form_post_form_alter.
   *
   * The method definition contains all standard parameters defined by the
   * definition of the hook.
   *
   * @param array $form
   *   The drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  #[Alter('form_post_form')]
  public function formPostFormAlter(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    assert($form_object instanceof ContentEntityForm, 'Expected $form_object to be an instance of ContentEntityForm.');

    if ($this->socialPostHelper->buildCurrentUserImage() !== NULL
      && $form_object->getEntity()->isNew()) {
      $form['current_user_image'] = $this->socialPostHelper->buildCurrentUserImage();
    }

    // Reset title display.
    $form['field_post']['widget'][0]['#title_display'] = '';

    // Set submit button caption to Post instead of Save.
    $form['actions']['submit']['#value'] = t('Post', [], ['context' => 'Post button']);

    // Default value.
    $titleAndPlaceholderValue = t('Say something to the Community');

    if ($form_state->get('currentGroup') !== NULL) {
      $titleAndPlaceholderValue = t('Say something to the group');
    }

    $user_profile = $form_state->get('recipientUser');
    if ($user_profile !== NULL
      && $user_profile->id() !== $this->currentUser->id()) {
      $titleAndPlaceholderValue = t('Leave a message to @name', [
        '@name' => $user_profile->getDisplayName(),
      ]);
    }

    // Set the title and placeholder value.
    $form['field_post']['widget'][0]['#title'] = $titleAndPlaceholderValue;
    $form['field_post']['widget'][0]['#placeholder'] = $titleAndPlaceholderValue;
  }

}
