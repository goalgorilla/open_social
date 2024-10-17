<?php

namespace Drupal\social_post\Hooks;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
    $content = $this->socialPostHelper->buildCurrentUserImage();
    $form_object = $form_state->getFormObject();

    if ($form_object instanceof ContentEntityForm === FALSE) {
      return;
    }

    if ($form_object->getEntity()->isNew()
      && $content !== NULL) {
      $form['current_user_image'] = $content;
    }

    // Reset title display.
    $form['field_post']['widget'][0]['#title_display'] = '';

    // Set submit button caption to Post instead of Save.
    $form['actions']['submit']['#value'] = t('Post', [], ['context' => 'Post button']);

    if (empty($form['field_post']) || empty($form['field_post']['widget'][0])) {
      return;
    }

    // Default value.
    $form = $this->setFormTitleAndPlaceholder($form, t('Say something to the Community'));

    if ($form_state->get('currentGroup') !== NULL) {
      $form = $this->setFormTitleAndPlaceholder($form, t('Say something to the group'));
    }

    // $user_profile = $this->routeMatch->getParameter('user');
    $user_profile = $form_state->get('recipientUser');
    if ($user_profile !== NULL
      && $user_profile->id() !== $this->currentUser->id()) {
      $form = $this->setFormTitleAndPlaceholder($form, t('Leave a message to @name', [
        '@name' => $user_profile->getDisplayName(),
      ]));
    }
  }

  /**
   * Set form title and placeholder value.
   *
   * @param array $form
   *   The drupal form.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $displayedValue
   *   The translatable markup value to display.
   *
   * @return array
   *   The updated form title and placeholder.
   */
  private function setFormTitleAndPlaceholder(array $form, TranslatableMarkup $displayedValue): array {
    $form['field_post']['widget'][0]['#title'] = $displayedValue;
    $form['field_post']['widget'][0]['#placeholder'] = $displayedValue;
    return $form;
  }

}
