<?php

namespace Drupal\social_event_an_enroll\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_event_managers\Plugin\Action\SocialEventManagersSendEmail;
use Drupal\user\UserInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send email to event enrollment users.
 *
 * @Action(
 *   id = "social_event_an_enroll_send_email_action",
 *   label = @Translation("Email"),
 *   type = "event_enrollment",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event_managers.vbo.confirm",
 * )
 */
class SocialEventAnEnrollSendEmail extends SocialEventManagersSendEmail {

  /**
   * The event enrollment.
   *
   * @var \Drupal\social_event\EventEnrollmentInterface
   */
  protected $entity;

  /**
   * The event an enroll manager.
   *
   * @var \Drupal\social_event_an_enroll\EventAnEnrollManager
   */
  protected $socialEventAnEnrollManager;

  /**
   * Constructs a SocialEventAnEnrollSendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param bool $allow_text_format
   *   TRUE if the current user can use the "Mail HTML" text format.
   * @param \Drupal\social_event_an_enroll\EventAnEnrollManager $social_event_an_enroll_manager
   *   The event an enroll manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    EmailValidator $email_validator,
    $allow_text_format,
    EventAnEnrollManager $social_event_an_enroll_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $token, $entity_type_manager, $logger, $mail_manager, $language_manager, $email_validator, $allow_text_format);

    $this->socialEventAnEnrollManager = $social_event_an_enroll_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('action'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('email.validator'),
      $container->get('current_user')->hasPermission('use text format mail_html'),
      $container->get('social_event_an_enroll.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->entity = $entity;

    if ($this->socialEventAnEnrollManager->isGuest($entity)) {
      $display_name = $this->socialEventAnEnrollManager->getGuestName($entity, FALSE);

      if (!$display_name) {
        $display_name = $this->t('Guest');
      }

      $this->configuration['display_name'] = $display_name;
    }

    parent::execute($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail(UserInterface $account) {
    if ($account->isAnonymous()) {
      return $this->entity->field_email->value;
    }

    return parent::getEmail($account);
  }

}
