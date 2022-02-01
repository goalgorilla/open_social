<?php

namespace Drupal\activity_send_email\Plugin\EmailBuilder;

use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailProcessorBase;
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
 *   }
 * )
 */
class ActivitySendEmailBuilder extends EmailProcessorBase implements ContainerFactoryPluginInterface {

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
    if ($subject = $email->getParam('subject')) {
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

    $email->setBody($email->getParam('body'));
  }

}
