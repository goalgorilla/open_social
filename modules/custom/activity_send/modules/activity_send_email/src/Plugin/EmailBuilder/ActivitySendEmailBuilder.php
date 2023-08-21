<?php

namespace Drupal\activity_send_email\Plugin\EmailBuilder;

use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the Email Builder plugin for the activity_send_email module.
 *
 * @EmailBuilder(
 *   id = "activity_send_email",
 *   sub_types = {
 *     "activity_send_email" = @Translation("Activity notification")
 *   },
 *   common_adjusters = {},
 *   import = @Translation("Activity notification"),
 * )
 */
class ActivitySendEmailBuilder extends EmailBuilderBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * Constructs an ActivitySendEmailBuilder object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(EmailInterface $email): void {
    $params = $email->getParam('params');
    if ($subject = $params['subject']) {
      $email->setSubject(strip_tags($subject));
    }
    else {
      $site_name = $this->configFactory->get('system.site')->get('name');
      $email->setSubject($this->t('Notification from %site_name', [
        '%site_name' => $site_name,
      ], [
        'langcode' => $email->getLangcode(),
      ]));
    }

    $email->setBody($params['body']);
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
