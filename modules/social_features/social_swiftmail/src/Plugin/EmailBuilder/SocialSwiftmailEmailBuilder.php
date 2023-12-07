<?php

namespace Drupal\social_swiftmail\Plugin\EmailBuilder;

use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;

/**
 * Defines the Email Builder plug-in for user module.
 *
 * @EmailBuilder(
 *   id = "social_swiftmail",
 *   sub_types = { "test" = @Translation("Test email") },
 *   common_adjusters = {"email_subject", "email_body"},
 * )
 */
class SocialSwiftmailEmailBuilder extends EmailBuilderBase {

  /**
   * Saves the parameters for a newly created email.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to modify.
   * @param mixed $params
   *   The params containing the site name.
   * @param mixed $to
   *   The to addresses, see Address::convert().
   */
  public function createParams(EmailInterface $email, $params = NULL, $to = NULL): void {
    $email->setParam('to', $to);
    $email->setParam('site_name', $params['site_name']);
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(EmailFactoryInterface $factory, array $message): EmailInterface {
    return $factory->newTypedEmail($message['module'], $message['key'], $message['params'], $message['to']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(EmailInterface $email): void {
    $email->setTo($email->getParam('to'));
    $email->setSubject($this->t('Social Mailer has been successfully configured!'));

    $text[] = '<p>' . $this->t('This e-mail has been sent from @site by the Social Mailer module. The module has been successfully configured.', ['@site' => $email->getParam('site_name')]) . '</p>';
    $text[] = $this->t('Kind regards') . '<br /><br />';
    $text[] = $this->t('The Social Mailer module');

    $body = [];
    $body['#type'] = 'processed_text';
    $body['#text'] = implode(PHP_EOL, $text);
    $body['#format'] = filter_default_format();

    $email->setBody($body);
  }

}
