<?php

namespace Drupal\social_event_an_enroll\Plugin\EmailBuilder;

use Drupal\Core\Render\Markup;
use Drupal\Core\Utility\Token;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the Email Builder plugin for the social_event_an_enroll module.
 *
 * @EmailBuilder(
 *   id = "social_event_an_enroll",
 *   sub_types = {
 *     "social_event_an_enroll" = @Translation("Anonymous Enrollment notification")
 *   },
 *   common_adjusters = {},
 *   import = @Translation("Anonymous Enrollment notification"),
 * )
 */
class AnonymousEventEnrollEmailBuilder extends EmailBuilderBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The token service.
   */
  protected Token $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('token'),
    );
  }

  /**
   * Constructs an EventInviteEmailBuilder object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ConfigFactoryInterface $config_factory,
    Token $token
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(EmailInterface $email): void {
    $config = $this->configFactory->getEditable('social_event_an_enroll.settings');
    $subject = $config->get('event_an_enroll_email_subject');
    $body = $config->get('event_an_enroll_email_body');

    $params = $email->getParam('params');

    $email->setSubject($this->token->replace($subject, $params));
    $email->setBody(Markup::create($this->token->replace($body, $params)));
  }

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
    $email->setParam('params', $params);
    $email->setParam('to', $to);
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
  }

}
