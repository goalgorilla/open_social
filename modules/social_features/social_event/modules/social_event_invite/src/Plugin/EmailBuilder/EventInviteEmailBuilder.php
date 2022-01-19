<?php

namespace Drupal\social_event_invite\Plugin\EmailBuilder;

use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailProcessorBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the Email Builder plugin for the social_event_invite module.
 *
 * This mail is sent when people who do not have an account on the website yet
 * are invited into an event. It is sent in the language the inviter was using
 * the website in.
 *
 * @EmailBuilder(
 *   id = "social_event_invite",
 *   sub_types = {
 *     "invite" = @Translation("Event invite")
 *   }
 * )
 */
class EventInviteEmailBuilder extends EmailProcessorBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The token service.
   */
  protected Token $token;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

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
      $container->get('language_manager'),
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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    Token $token,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(EmailInterface $email): void {
    $language = $this->languageManager->getLanguage($email->getLangcode());
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($language);

    $config = $this->configFactory->getEditable('social_event_invite.settings');
    $subject = $config->get('invite_subject');
    $body = $config->get('invite_message');

    $params = $email->getParams();
    unset($params['existing_user']);

    $email->setSubject($this->token->replace($subject, $params));
    $email->setBody(Markup::create($this->token->replace($body, $params)));

    $this->languageManager->setConfigOverrideLanguage($original_language);
  }

}
