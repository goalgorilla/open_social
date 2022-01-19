<?php

namespace Drupal\social_mailer\Plugin\EmailBuilder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer_bc\Plugin\EmailBuilder\UserEmailBuilder as UserEmailBuilderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom email builder plugin for the user module.
 *
 * @see social_mailer_mailer_builder_info_alter()
 */
class UserEmailBuilder extends UserEmailBuilderBase implements ContainerFactoryPluginInterface {

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
   * Constructs a UserEmailBuilder object.
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
    parent::preRender($email);

    $config = $this->configFactory->getEditable('user.mail');
    $type = $email->getSubType();
    $email->setSubject($config->get("{$type}.subject"));
    $email->setBody([
      '#type' => 'processed_text',
      '#text' => $config->get("{$type}.body"),
    ]);
  }

}
