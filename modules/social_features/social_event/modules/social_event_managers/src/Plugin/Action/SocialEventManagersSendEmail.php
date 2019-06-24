<?php

namespace Drupal\social_event_managers\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_user\Plugin\Action\SocialSendEmail;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;

/**
 * Send email to event enrollment users.
 *
 * @Action(
 *   id = "social_event_managers_send_email_action",
 *   label = @Translation("Send email to event enrollment users"),
 *   type = "event_enrollment",
 *   view_id = "event_manage_enrollments",
 *   display_id = "page_manage_enrollments",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event_managers.vbo.confirm",
 * )
 */
class SocialEventManagersSendEmail extends SocialSendEmail {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
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
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $token, $entity_type_manager, $logger, $mail_manager, $language_manager, $email_validator, $config_factory);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    parent::execute($entity->field_account->entity);

    return $this->t('Send mail');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);

    if ($object instanceof EventEnrollmentInterface) {
      $access = $object->access('delete', $account, TRUE);

      $event_id = $object->getFieldValue('field_event', 'target_id');
      $node = $this->entityTypeManager->getStorage('node')->load($event_id);

      // Also Event organizers can do this.
      if ($node instanceof NodeInterface && social_event_manager_or_organizer($node)) {
        $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);
      }
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Add title to the form as well.
    if ($form['#title'] !== NULL) {
      $selected_count = $this->context['selected_count'];
      $subtitle = $this->formatPlural($selected_count,
        'Configure the email you want to send to the one enrollee you have selected.',
        'Configure the email you want to send to the @count enrollees you have selected.'
      );

      $form['subtitle'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['placeholder'],
        ],
        '#value' => $subtitle,
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

}
