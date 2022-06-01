<?php

namespace Drupal\social_group_invite\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ginvite\Form\BulkGroupInvitation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class SocialBulkGroupInvitation.
 */
class SocialBulkGroupInvitation extends BulkGroupInvitation {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The Membership Loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * The Config factory.
   *
   * @var \Drupal\ginvite\GroupInvitationLoader
   */
  protected $groupInvitationLoader;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected FileUrlGenerator $fileUrlGenerator;

  /**
   * Constructs a new BulkGroupInvitation Form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\ginvite\GroupInvitationLoader $invitation_loader
   *   Invitations loader service.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\File\FileUrlGenerator $file_url_generator
   *   The file url generator service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    PrivateTempStoreFactory $temp_store_factory,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    GroupMembershipLoaderInterface $group_membership_loader,
    GroupInvitationLoader $invitation_loader,
    GroupContentEnablerManagerInterface $plugin_manager,
    ConfigFactoryInterface $config_factory,
    Token $token,
    FileUrlGenerator $file_url_generator
  ) {
    parent::__construct($route_match, $entity_type_manager, $temp_store_factory, $logger_factory, $messenger, $group_membership_loader, $invitation_loader);
    $this->group = $this->routeMatch->getParameter('group');
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->groupInvitationLoader = $group_membership_loader;
    $this->groupMembershipLoader = $invitation_loader;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('group.membership_loader'),
      $container->get('ginvite.invitation_loader'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('config.factory'),
      $container->get('token'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_bulk_group_invitation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attributes']['class'][] = 'form--default';

    $group = $this->routeMatch->getParameter('group');

    $params = [
      'user' => $this->currentUser(),
      'group' => $this->routeMatch->getParameter('group'),
    ];

    // Replace recipients field by select2.
    unset($form['email_address']);

    $form['users_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'class' => [
          'form-horizontal',
        ],
      ],
    ];

    $form['users_fieldset']['user'] = [
      '#title' => $this->t('Find people by name or email address'),
      '#type' => 'select2',
      '#description' => $this->t('You can enter or paste multiple entries separated by comma or semicolon'),
      '#multiple' => TRUE,
      '#tags' => TRUE,
      '#autocomplete' => TRUE,
      '#selection_handler' => 'social',
      '#target_type' => 'user',
      '#select2' => [
        'tags' => TRUE,
        'placeholder' => t('Jane Doe, johndoe@example.com'),
        'tokenSeparators' => [',', ';'],
        'autocomplete' => FALSE,
      ],
      '#required' => TRUE,
      '#validated' => TRUE,
    ];

    // Load plugin configuration.
    $group_plugin_collection = $this->pluginManager->getInstalled($group->getGroupType());
    $group_invite_config = $group_plugin_collection->getConfiguration()['group_invitation'];

    // Get invite settings.
    $invite_settings = $this->configFactory->get('social_group.settings')->get('group_invite');

    // Set preview subject and message.
    $invitation_subject = $invite_settings['invite_subject'] ?? $group_invite_config['invitation_subject'];
    $invitation_body = $invite_settings['invite_message'] ?? $group_invite_config['invitation_body'];

    // Cleanup message body and replace any links on preview page.
    $invitation_body = $this->token->replace($invitation_body, $params);
    $invitation_body = preg_replace('/href="([^"]*)"/', 'href="#"', $invitation_body);

    // Get default logo image and replace if it overridden with email settings.
    $theme_id = $this->configFactory->get('system.theme')->get('default');
    $logo = $this->getRequest()->getBaseUrl() . theme_get_setting('logo.url', $theme_id);
    $email_logo = theme_get_setting('email_logo', $theme_id);

    if (is_array($email_logo) && !empty($email_logo)) {
      $file = File::load(reset($email_logo));

      if ($file instanceof File) {
        $logo = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    // Load event invite configuration.
    $invite_config = $this->configFactory->get('social_group_invite.settings');

    $form['email_preview'] = [
      '#type' => 'fieldset',
      '#title' => [
        'text' => [
          '#markup' => t('Preview your email invite'),
        ],
        'icon' => [
          '#markup' => '<svg class="icon icon-expand_more"><use xlink:href="#icon-expand_more" /></svg>',
          '#allowed_tags' => ['svg', 'use'],
        ],
      ],
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => [
        'class' => [
          'form-horizontal',
          'form-preview-email',
        ],
      ],
    ];

    $form['email_preview']['preview'] = [
      '#theme' => 'invite_email_preview',
      '#logo' => $logo,
      '#subject' => $this->token->replace($invitation_subject, $params),
      '#body' => $invitation_body,
      '#helper' => $this->token->replace($invite_config->get('invite_helper'), $params),
    ];

    $form['actions']['#type'] = 'actions';
    unset($form['submit']);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send your invite(s) by email'),
    ];

    return $form;
  }

  /**
   * Custom form validation.
   *
   * We override the source form validation, because we offer invitations to
   * both emails and existing users. Also we unset the people already invited,
   * or part of the group so we don't force users a step back to the buildform.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Loop through all the entries.
    foreach ($form_state->getValue('users_fieldset')['user'] as $user) {
      $email = $this->extractEmailsFrom($user);

      // If the entry is an email go through some different checks then
      // when the user would already be part of the platform.
      if ($email) {
        // Load the user by email.
        $account = user_load_by_mail($email);

        // Check if the email is already part of the platform.
        if ($account instanceof UserInterface) {
          $membership = $this->groupMembershipLoader->load($this->group, $account);
          // User is already part of the group, unset it from the list and
          // show an error.
          if (!empty($membership) && !$this->validateExistingMembers([$account], $form_state)) {
            $form_state->unsetValue(['users_fieldset', 'user', $user]);
            return;
          }
        }
        else {
          /** @var \Drupal\group\Entity\Storage\GroupContentStorageInterface $group_content_storage */
          $group_content_storage = $this->entityTypeManager->getStorage('group_content');
          // If the invitation has already been send, unset it from the list
          // and show an error.
          // For some reason groupInvitationLoader service doesn't work
          // properly.
          if (!empty($group_content_storage->loadByGroup($this->group, 'group_invitation', ['invitee_mail' => $email]))) {
            $form_state->unsetValue(['users_fieldset', 'user', $user]);

            $message_singular = "User with @error_message e-mail has already been invited.";
            $message_plural = "Users with: @error_message e-mails were already invited.";

            $this->displayErrorMessage([$email], $message_singular, $message_plural, $form_state);

            return;
          }
        }
      }
      else {
        // Load the user by userId.
        $account = User::load($user);

        if ($account instanceof UserInterface) {
          $membership = $this->groupMembershipLoader->load($this->group, $account);
          // User is already part of the group, unset it from the list
          // and show an error.
          if (!empty($membership) && !$this->validateExistingMembers([$account], $form_state)) {
            $form_state->unsetValue(['users_fieldset', 'user', $user]);
            return;
          }
          else {
            // Change the uservalue to email because the bulk invite for
            // groups can only handle emails.
            $form_state->setValue(['users_fieldset', 'user', $user], $account->getEmail());
          }
        }
      }
    }

  }

  /**
   * Validate if emails belong to existing group member,display an error if so.
   *
   * @param array $users
   *   Array of user entities to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Returns TRUE if the user is not part of the group.
   */
  private function validateExistingMembers(array $users, FormStateInterface $form_state) {
    $invalid_emails = [];

    foreach ($users as $user) {
      $membership = $this->groupMembershipLoader->load($this->group, $user);
      if (!empty($membership)) {
        $invalid_emails[] = $user->getEmail();
      }
    }

    if (!empty($invalid_emails)) {
      $message_singular = "User with @error_message e-mail already a member of this group.";
      $message_plural = "Users with: @error_message e-mails already members of this group.";

      $this->displayErrorMessage($invalid_emails, $message_singular, $message_plural, $form_state);

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Prepares form error message if there is invalid emails.
   *
   * @param array $invalid_emails
   *   List of invalid emails.
   * @param string $message_singular
   *   Error message for one invalid email.
   * @param string $message_plural
   *   Error message for multiple invalid emails.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function displayErrorMessage(array $invalid_emails, $message_singular, $message_plural, FormStateInterface $form_state) : void {
    $count = count($invalid_emails);

    if ($count > 1) {
      $error_message = '<ul>';
      foreach ($invalid_emails as $line => $invalid_email) {
        $error_message .= "<li>{$invalid_email} on line {$line}</li>";
      }
      $error_message .= '</ul>';
      $form_state->setErrorByName('email_address', $this->formatPlural($count, $message_singular, $message_plural, ['@error_message' => new FormattableMarkup($error_message, [])]));
    }
    elseif ($count == 1) {
      $error_message = reset($invalid_emails);
      $form_state->setErrorByName('email_address', $this->formatPlural($count, $message_singular, $message_plural, ['@error_message' => $error_message]));
    }
  }

  /**
   * Custom form submit.
   *
   * We override the source form submit, because we skip the confirm page to be
   * consistent with inviting for events.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Override the Batch created in BulkGroupInvitation
    // this so we can create a better message, use a new redirect in the
    // finished argument but also update the correct emails etc.
    $batch = [
      'title' => $this->t('Inviting Members'),
      'operations' => [],
      'init_message'     => $this->t('Sending Invites'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message'    => $this->t('An error occurred during processing'),
      'finished' => 'Drupal\social_group_invite\Form\SocialBulkGroupInvitation::batchFinished',
    ];

    foreach ($form_state->getValue('users_fieldset')['user'] as $email) {
      $email = $this->extractEmailsFrom($email);

      // Make sure to only add valid emails to the batch.
      if ($email) {
        $values = [
          'type' => $this->group->getGroupType()
            ->getContentPlugin('group_invitation')
            ->getContentTypeConfigId(),
          'gid' => $this->group->id(),
          'invitee_mail' => $email,
          'entity_id' => 0,
        ];
        $batch['operations'][] = ['\Drupal\ginvite\Form\BulkGroupInvitationConfirm::batchCreateInvite', [$values]];
      }
    }

    // Prepare params to store them in tempstore.
    $params = [];
    $params['gid'] = $this->group->id();
    $params['plugin'] = $this->group->getGroupType()->getContentPlugin('group_invitation')->getContentTypeConfigId();
    $params['emails'] = $this->getSubmittedEmails($form_state);

    $tempstore = $this->tempStoreFactory->get('ginvite_bulk_invitation');
    $tempstore->set('params', $params);

    batch_set($batch);
  }

  /**
   * Get array of submited emails.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   List of emails to invite .
   */
  private function getSubmittedEmails(FormStateInterface $form_state) {
    return array_map('trim', array_unique(explode("\r\n", trim($form_state->getValue('email_address')))));
  }

  /**
   * Batch finished callback overridden from BulkGroupInvitationConfirm.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      try {
        $tempstore = \Drupal::service('tempstore.private')->get('ginvite_bulk_invitation');
        $params = $tempstore->get('params')['gid'];
        // BulkGroupInvitationConfirm sends us to
        // $destination = new Url('view.group_invitations.page_1',
        // ['group' => $tempstore->get('params')['gid']]);
        // however we want to go to the group canonical.
        $destination = new Url('entity.group.canonical', ['group' => $tempstore->get('params')['gid']]);
        $redirect = new RedirectResponse($destination->toString());
        $tempstore->delete('params');
        $redirect->send();
      }
      catch (\Exception $error) {
        \Drupal::service('logger.factory')->get('social_group_invite')->alert(new TranslatableMarkup('@err', ['@err' => $error]));
      }

    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')->addMessage(new TranslatableMarkup('An error occurred while processing @operation with arguments : @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0]),
      ]));
    }
  }

  /**
   * Custom function to extract email addresses from a string.
   */
  public function extractEmailsFrom($string) {
    // Remove select2 ID parameter.
    $string = str_replace('$ID:', '', $string);
    preg_match_all("/[\._a-zA-Z0-9+-]+@[\._a-zA-Z0-9+-]+/i", $string, $matches);

    if (is_array($matches[0]) && count($matches[0]) === 1) {
      return reset($matches[0]);
    }

    return $matches[0];
  }

  /**
   * Returns access to the invite page.
   *
   * @param \Drupal\group\Entity\GroupInterface|mixed[] $group
   *   The group entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access results.
   */
  public function inviteAccess(GroupInterface $group) {
    // Allow for Group admin/managers.
    if ($group->hasPermission('administer members', $this->currentUser())) {
      return AccessResult::allowed();
    }

    // Disable access for non-members or invite by members was disabled in the
    // group settings.
    $group_settings = $this->config('social_group.settings');
    if (
      !(bool) $group_settings->get('group_invite.invite_by_members') ||
      !$group->getMember($this->currentUser())
    ) {
      return AccessResult::forbidden();
    }

    // Allow sharing/invites for members only if allowed by the group manager.
    if (
      $group->hasField('field_group_invite_by_member') &&
      !$group->get('field_group_invite_by_member')->isEmpty() &&
      $group->get('field_group_invite_by_member')->getString() === '1'
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

}
