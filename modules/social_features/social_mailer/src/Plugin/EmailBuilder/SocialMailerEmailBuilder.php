<?php

namespace Drupal\social_mailer\Plugin\EmailBuilder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailProcessorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Email builder for the social_mailer module.
 *
 * @EmailBuilder(
 *   id = "social_mailer",
 *   sub_types = {
 *     "test" = @Translation("Test email")
 *   }
 * )
 */
class SocialMailerEmailBuilder extends EmailProcessorBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

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
      $container->get('current_user'),
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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(EmailInterface $email): void {
    parent::preRender($email);

    $email->setSubject($this->t('Social Mailer has been successfully configured!'));

    $text[] = '<h3>' . $this->t('Dear @user,', [
      '@user' => $this->currentUser->getDisplayName(),
    ]) . '</h3>';
    $text[] = '<p>' . $this->t('This e-mail has been sent from @site by the Social Mailer module. The module has been successfully configured.', [
      '@site' => $this->configFactory->get('system.site')->get('name'),
    ]) . '</p>';
    $text[] = $this->t('Kind regards') . '<br /><br />';
    $text[] = $this->t('The Social Mailer module');
    $email->setBody(Markup::create(implode(PHP_EOL, $text)));
  }

}
