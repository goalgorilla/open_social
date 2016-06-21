<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserPasswordForm;

/**
 * Class SocialUserPasswordForm.
 *
 * @package Drupal\social_user\Form
 */
class SocialUserPasswordForm extends UserPasswordForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_user_password_form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation necessary to protect the privacy of users.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(array('mail' => $name));
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(array('name' => $name));
    }
    $account = reset($users);
    if ($account && $account->id() && $account->isActive()) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      // Mail one time login URL and instructions using current language.
      $mail = _user_mail_notify('password_reset', $account, $langcode);
      if (!empty($mail)) {
        $this->logger('user')
          ->notice('Password reset instructions mailed to %name at %email.', array(
            '%name' => $account->getUsername(),
            '%email' => $account->getEmail(),
          ));
      }
    }
    drupal_set_message(t('Due to privacy concerns, the policy of this web site is not to disclose the existence of registered email addresses.
     Hence, if you entered a valid email address or username, a password reset link is now being sent to it.
     If the email address or username you entered does not exist, you will not get a reset link, and will neither get any confirmation or warning about that here.
     Contact the site administrator if there are any problems.'));

    $form_state->setRedirect('user.page');
  }

}
