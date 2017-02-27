<?php

namespace Drupal\social_auth_twitter\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class TwitterAuthLoginNoticeForm
 * @package Drupal\social_auth_twitter\Form
 */
class TwitterAuthLoginNoticeForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Create new account');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twitter_auth_login_notice_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Log in');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('There is no account connected to this @network account. You can create new account.', [
      '@network' => $this->t('Twitter'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('user.login');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('social_auth_twitter.user_register');
  }

}