<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['forgot'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reset password with <b>username</b> or <b>email</b>'),
    ];

    // Move name and mail into the fieldset.
    $form['forgot']['name'] = $form['name'];
    $form['forgot']['mail'] = $form['mail'];

    unset($form['name']);
    unset($form['mail']);

    if (\Drupal::config('user.settings')->get('register') != 'admin_only') {
      // Link to the login/register pages.
      $sign_up_link = Link::createFromRoute($this->t('Sign up'), 'user.register')->toString();

      $form['forgot']['sign-up-link'] = [
        '#markup' => $this->t("Don't have an account yet? @link", ["@link" => $sign_up_link]),
      ];
    }
    return $form;
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
    $users = $this->userStorage->loadByProperties(['mail' => $name]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name]);
    }
    $account = reset($users);
    if ($account && $account->id() && $account->isActive()) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      // Mail one time login URL and instructions using current language.
      $mail = _user_mail_notify('password_reset', $account, $langcode);
      if (!empty($mail)) {
        $this->logger('user')
          ->notice('Password reset instructions mailed to %name at %email.', [
            '%name' => $account->getUsername(),
            '%email' => $account->getEmail(),
          ]);
      }
    }
    $site_config = $this->config('system.site');
    $site_mail = $site_config->get('mail');
    $show_mail = $site_config->get('show_mail_in_messages');
    $admin_link = ($site_mail && $show_mail) ? Link::fromTextAndUrl(t('site administrator'), Url::fromUri('mailto:' . $site_mail))->toString() : t('site administrator');
    $this->messenger()->addStatus(t('Due to privacy concerns, the identity of registered email addresses will not be disclosed. Therefore, a password reset link has been sent to you only if you have entered a valid email address or username.
<br>If the email address or username you entered does not exist, you will not get a reset link or a confirmation/warning about that here. Please contact the @admin_link if there are any problems.',
      ['@admin_link' => $admin_link]));

    $form_state->setRedirect('user.page');
  }

}
