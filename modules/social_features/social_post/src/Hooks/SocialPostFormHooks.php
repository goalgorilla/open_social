<?php

namespace Drupal\social_post\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\hux\Attribute\Alter;
use Drupal\social_group\CurrentGroupService;
use Drupal\social_post\Service\SocialPostHelperInterface;


final class SocialPostFormHooks {

  private SocialPostHelperInterface $socialPostHelper;
  private AccountProxyInterface $currentUser;

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
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  #[Alter('form_post_form')]
  public function formPostFormAlter(array &$form, FormStateInterface $form_state): void {
    $content = $this->socialPostHelper->buildCurrentUserImage();

    if ($form_state->getFormObject()->getEntity()->isNew()
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
      && $user_profile->id() != $this->currentUser->id()) {
      $this->setFormTitleAndPlaceholder($form, t('Leave a message to @name', [
        '@name' => $user_profile->getDisplayName(),
      ]));
    }
  }

  /**
   * Set form title and placeholder value.
   *
   * @param array $form
   * @param TranslatableMarkup $displayedValue
   *
   * @return array
   */
  private function setFormTitleAndPlaceholder(array $form, TranslatableMarkup $displayedValue): array {
    $form['field_post']['widget'][0]['#title'] = $displayedValue;
    $form['field_post']['widget'][0]['#placeholder'] = $displayedValue;
    return $form;
  }

}
