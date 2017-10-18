<?php

namespace Drupal\social_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserLoginForm;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class SocialUserLoginForm.
 *
 * This custom login form is build for the following reasons:
 *  - Enable users to log in with their email address.
 *  - Protect the privacy of users by not leaking the username and email address
 *    in case of validation errors.
 *
 * Almost all code is altered slightly from the UserLoginForm.
 *
 * @package Drupal\social_user\Form
 */
class SocialUserLoginForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_user_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system.site');

    // Display login form:
    $form['name_or_mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username or email address'),
      '#size' => 60,
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#description' => $this->t('Enter your @s username or email.', ['@s' => $config->get('name')]),
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];

    $reset_pass_url = Url::fromRoute('user.pass');
    $reset_pass_link = Link::createFromRoute($this->t('Forgot password?'), $reset_pass_url->getRouteName());
    $generated_reset_pass_link = $reset_pass_link->toString();
    $pass_description = $generated_reset_pass_link->getGeneratedLink();

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#description' => $pass_description,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Log in')];

    // Validates account and sets the form state uid that is used in the
    // submit function.
    $form['#validate'][] = '::validateAuthentication';
    // Validates if the uid is set and display an error message if the input
    // is invalid.
    // Validates if a user with a username or email is blocked.
    $form['#validate'][] = '::validateNameMail';
    $form['#validate'][] = '::validateFinal';

    $this->renderer->addCacheableDependency($form, $config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->userStorage->load($form_state->get('uid'));
    // A destination was set, probably on an exception controller,.
    // @TODO: Add validation if route exists.
    if (!$this->getRequest()->request->has('destination')) {
      $form_state->setRedirect('<front>');
    }
    else {
      $this->getRequest()->query->set('destination', $this->getRequest()->request->get('destination'));
    }

    user_login_finalize($account);
  }

  /**
   * Sets an error if supplied username or mail has been blocked.
   */
  public function validateNameMail(array &$form, FormStateInterface $form_state) {
    $user_blocked = social_user_is_blocked($form_state->getValue('name_or_mail'));
    if ($user_blocked) {
      // Blocked in user administration.
      if (!$form_state->get('uid')) {
        $this->setGeneralErrorMessage($form, $form_state);
      }
      else {
        $form_state->setErrorByName('name_or_mail', $this->t('The username %name has not been activated or is blocked.', ['%name' => $form_state->getValue('name_or_mail')]));
      }
    }
  }

  /**
   * Checks supplied username/password against local users table.
   *
   * If successful, $form_state->get('uid') is set to the matching user ID.
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    if (!$form_state->isValueEmpty('name_or_mail') && strlen($password) > 0) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one IP for all users at an institution.
      if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        return;
      }

      // Try to retrieve the account with the mail address.
      $name = $form_state->getValue('name_or_mail');
      $accounts = $this->userStorage->loadByProperties(['mail' => $form_state->getValue('name_or_mail'), 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        $name = $account->getAccountName();
      }

      $accounts = $this->userStorage->loadByProperties(['name' => $name, 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if (!$this->flood->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $form_state->set('flood_control_triggered', 'user');
          return;
        }
      }
      // We are not limited by flood control, so try to authenticate.
      // Store $uid in form state as a flag for self::validateFinal().
      $uid = $this->userAuth->authenticate($name, $password);
      $form_state->set('uid', $uid);
    }
  }

  /**
   * Checks if user was not authenticated, or if too many logins were attempted.
   *
   * This validation function should always be the last one.
   */
  public function validateFinal(array &$form, FormStateInterface $form_state) {
    $flood_config = $this->config('user.flood');
    if (!$form_state->get('uid')) {
      // Set general error message to not leak privacy information.
      $this->setGeneralErrorMessage($form, $form_state);

      // Always register an IP-based failed login event.
      $this->flood->register('user.failed_login_ip', $flood_config->get('ip_window'));
      // Register a per-user failed login event.
      if ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
        $this->flood->register('user.failed_login_user', $flood_config->get('user_window'), $flood_control_user_identifier);
      }
      $flood_control_triggered = $form_state->get('flood_control_triggered');
      if (!$flood_control_triggered) {
        $name = $form_state->getValue('name_or_mail');
        $accounts = $this->userStorage->loadByProperties(['mail' => $form_state->getValue('name_or_mail'), 'status' => 1]);
        $account = reset($accounts);
        if ($account) {
          $name = $account->getAccountName();
        }
        $accounts = $this->userStorage->loadByProperties(['name' => $name]);
        if (!empty($accounts)) {
          $this->logger('user')->notice('Login attempt failed for %user.', ['%user' => $name]);
        }
        else {
          // If the username entered is not a valid user,
          // only store the IP address.
          $this->logger('user')->notice('Login attempt failed from %ip.', ['%ip' => $this->getRequest()->getClientIp()]);
        }
      }
    }
    elseif ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
      // Clear past failures for this user so as not to block a user who might
      // log in and out more than once in an hour.
      $this->flood->clear('user.failed_login_user', $flood_control_user_identifier);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setGeneralErrorMessage(array &$form, FormStateInterface $form_state) {
    $form_state->setErrorByName('name_or_mail', $this->t('
        There was an error :( This could happen for one of for the following reasons: <br>
        - Unrecognized username/email and password combination. <br>
        - There has been more than one failed login attempt for this account. It is temporarily blocked. <br>
        - Too many failed login attempts from your IP address. This IP address is temporarily blocked. <br> <br>
        To solve the issue try other credentials, try again later or <a href=":url">request a new password</a>',
      ['%name_or_email' => $form_state->getValue('name_or_mail'), ':url' => $this->url('user.pass')]));
  }

}
