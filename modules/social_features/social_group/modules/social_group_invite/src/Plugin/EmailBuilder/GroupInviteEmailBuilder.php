<?php

namespace Drupal\social_group_invite\Plugin\EmailBuilder;

use Drupal\group\Entity\GroupContentInterface;
use Drupal\social_group_invite\Plugin\Action\SocialGroupInviteResend;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Defines the Email Builder plugin for the social_group_invite module.
 *
 * This mail is sent when people who do not have an account on the website yet
 * are invited into an event. It is sent in the language the inviter was using
 * the website in.
 *
 * @EmailBuilder(
 *   id = "ginvite",
 *   sub_types = {
 *     "invite" = @Translation("Group invite")
 *   }
 * )
 */
class GroupInviteEmailBuilder extends EmailBuilderBase implements ContainerFactoryPluginInterface {

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
    $params = $email->getParam('params');
    $token_service = \Drupal::token();
    $language_manager = \Drupal::languageManager();

    $langcode = $email->getLangcode();

    $language = $language_manager->getLanguage($langcode);
    $original_language = $language_manager->getConfigOverrideLanguage();
    $language_manager->setConfigOverrideLanguage($language);

    // Load group invite configuration.
    $group_config = \Drupal::config('social_group.settings');
    $invite_settings = $group_config->get('group_invite');

    // The mail params list should contain group content entity.
    /* @see ginvite_group_content_insert() */
    /** @var \Drupal\group\Entity\GroupContentInterface $invite */
    $invite = $params['group_content'] ?? NULL;

    // Alter message and subject if it configured.
    if (
      !is_null($invite_settings) &&
      isset($invite_settings['invite_subject'], $invite_settings['invite_message'])
    ) {
      // Check if the invitation is resent and site managers decided to change
      // the invitation email text.
      if ($invite_settings['invite_resend_message']) {
        $tempstore = \Drupal::service('tempstore.shared')->get('social_group_invite');
        $resent_invites = (array) $tempstore->get(SocialGroupInviteResend::TEMP_STORE_ID);
        if (!empty($resent_invites)) {

          if (
            $invite instanceof GroupContentInterface &&
            in_array($invite->uuid(), $resent_invites)
          ) {
            $overridden_body = $invite_settings['invite_resend_message'];
            // Remove handled resent invite from list.
            unset($resent_invites[$params['group_content']->uuid()]);
            $tempstore->set(SocialGroupInviteResend::TEMP_STORE_ID, $resent_invites);
          }
        }
      }

      if ($invite instanceof GroupContentInterface) {
        // Allows to have different invite message per group type by replacing
        // default global message.
        /** @var Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation $group_content_plugin */
        $group_content_plugin = $invite->getContentPlugin();

        if ($group_content_plugin->getPluginId() === 'group_invitation') {
          $configuration = $group_content_plugin->getConfiguration();

          if ($subject = $configuration['invitation_subject'] ?? '') {
            $invite_settings['invite_subject'] = $subject;
          }
          if ($body = $configuration['invitation_body'] ?? '') {
            $invite_settings['invite_message'] = $body;
          }
        }
      }

      $invitation_subject = $invite_settings['invite_subject'];
      $invitation_body = $overridden_body ?? $invite_settings['invite_message'];

      unset($params['existing_user']);

      $email->setSubject($this->token->replace($invitation_subject, $params));
      $email->setBody(Markup::create($this->token->replace($invitation_body, $params)));

    }
    $language_manager->setConfigOverrideLanguage($original_language);
  }

  /**
   * Saves the parameters for a newly created email.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to modify.
   * @param mixed $params
   *   The params containing the site name
   * @param mixed $to
   *   The to addresses, see Address::convert().
   *
   */
  public function createParams(EmailInterface $email, $params = NULL, $to = NULL) {
    $email->setParam('params', $params);
    $email->setParam('to', $to);
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(EmailFactoryInterface $factory, array $message) {
    return $factory->newTypedEmail($message['module'], $message['key'], $message['params'], $message['to']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(EmailInterface $email) {
    $email->setTo($email->getParam('to'));
  }

}
