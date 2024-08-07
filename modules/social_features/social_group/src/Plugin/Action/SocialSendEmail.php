<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Utility\Token;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_email_broadcast\SocialEmailBroadcast;
use Drupal\social_user\Plugin\Action\SocialSendEmail as SocialSendEmailBase;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send an email to group members.
 *
 * @Action(
 *   id = "social_group_send_email_action",
 *   label = @Translation("Send email to group members"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class SocialSendEmail extends SocialSendEmailBase {


  use ViewsBulkOperationsFormTrait;

  /**
   * The Drupal module handler service.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The tempstore service.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The current user object.
   */
  protected AccountInterface $currentUser;

  /**
   * The email broadcast service.
   */
  protected SocialEmailBroadcast $emailBroadcast;

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
    LanguageManagerInterface $language_manager,
    EmailValidator $email_validator,
    QueueFactory $queue_factory,
    $allow_text_format,
    ModuleHandlerInterface $module_handler,
    PrivateTempStoreFactory $temp_store_factory,
    AccountInterface $current_user,
    SocialEmailBroadcast $email_broadcast_service
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $token,
      $entity_type_manager,
      $logger,
      $language_manager,
      $email_validator,
      $queue_factory,
      $allow_text_format
    );

    $this->moduleHandler = $module_handler;
    $this->tempStoreFactory = $temp_store_factory;
    $this->currentUser = $current_user;
    $this->emailBroadcast = $email_broadcast_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('action'),
      $container->get('language_manager'),
      $container->get('email.validator'),
      $container->get('queue'),
      $container->get('current_user')->hasPermission('use text format mail_html'),
      $container->get('module_handler'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get(SocialEmailBroadcast::class),
    );
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser(): AccountInterface {
    return $this->currentUser;
  }

  /**
   * Helps to check if a user is unsubscribed or not from bulk mailing.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return bool
   *   If unsubscribed TRUE, otherwise FALSE.
   *
   * @throws \Exception
   */
  private function isUnsubscribedFromEmails(UserInterface $user): bool {
    if (empty($this->context['group_type'])) {
      $this->logger->error($this->t('Using action @action out of the group context!', ['@action' => __CLASS__]));
      // Hardly restrict sending emails if a group type isn't detected.
      return TRUE;
    }

    if (!$user->isAuthenticated()) {
      return FALSE;
    }

    $setting_name = $this->getUnsubscribeSettingName();
    if (empty($setting_name)) {
      return FALSE;
    }

    $frequency = $this->emailBroadcast->getBulkEmailUserSetting(account: $user, name: $setting_name);
    return !empty($frequency) && $frequency === SocialEmailBroadcast::FREQUENCY_NONE;
  }

  /**
   * Helps to get unsubscribe setting.
   *
   * @return string
   *   The name of unsubscribe setting or empty.
   */
  private function getUnsubscribeSettingName(): string {
    $items = [];
    $this->moduleHandler->alter('social_email_broadcast_notifications', $items);

    if (empty($items['community_updates']['bulk_mailing'])) {
      return '';
    }

    foreach ($items['community_updates']['bulk_mailing'] as $setting) {
      if (empty($setting['entity_type']['group'])) {
        continue;
      }

      if (in_array($this->context['group_type'], (array) $setting['entity_type']['group'])) {
        $setting_name = $setting['name'];
        break;
      }
    }

    return $setting_name ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    if ($object instanceof GroupRelationshipInterface) {
      return $object->access('view', $account, $return_as_object);
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(array &$context): void {
    parent::setContext($context);

    // @todo Rely on something more solid then dynamic data for batch.
    // We don't want to run this code if batch was started.
    // "prepopulated" key is adding exactly on batch start.
    if (isset($context['prepopulated'])) {
      return;
    }

    if (!isset($context['group_type'])) {
      if (!$group_id = $context['group_id'] ?? NULL) {
        // We need to know in what group context current action is executed.
        return;
      }

      $group = Group::load($group_id);
      if (!$group instanceof GroupInterface) {
        return;
      }

      $context['group_type'] = $group->bundle();
      // Update a context property values as it needed farther.
      parent::setContext($context);
    }

    if (!isset($context['list'], $context['bulk_form_keys'])) {
      // Probably, the method is executed on batch processing.
      return;
    }

    // Filter members that have disabled bulk mailing based on their profile
    // settings.
    // We need to remove the selected users from temporary data and update
    // form data passed through the further forms.
    if ($select_all = empty($context['list'])) {
      $selected_options = $context['bulk_form_keys'];
    }
    else {
      $selected_options = array_keys($context['list']);
    }

    if (!$selected_options) {
      return;
    }

    $origin = [...$selected_options];

    // Go through each membership and check if a user doesn't have
    // a disabled bulk mailing.
    foreach ($selected_options as $key => $name) {
      $item = (array) $this->getListItem($name);
      if (!in_array('group_content', $item)) {
        continue;
      }

      // First element is the enrollment ID.
      $gcid = $item[0] ?? 0;
      $group_membership = GroupRelationship::load($gcid);
      if (!$group_membership) {
        continue;
      }

      /** @var \Drupal\user\UserInterface $account */
      $account = $group_membership->getEntity();

      // Check user frequency settings for group type bulk mailing.
      // If a user has disabled mailing, we remove enrollment from
      // the selected list.
      // Only authenticated users have frequency settings.
      if ($this->isUnsubscribedFromEmails($account)) {
        unset($selected_options[$key]);
      }
    }

    // All selected users can receive the email.
    if (!$removed = array_diff($origin, $selected_options)) {
      $context['selected_removed'] = 0;
      return;
    }

    // If some of the selected members unsubscribed from emails, we should
    // prevent executing action for all memberships.
    if ($select_all) {
      $context['exclude_mode'] = FALSE;
    }

    $context['selected_removed'] = count($removed);
    $context['selected_count'] = count($selected_options);
    $context['bulk_form_keys'] = $selected_options;

    // If event managers pressed "Select all" button, and we found the
    // memberships that have disabled bulk mailing, we should change below
    // options to prevent sending emails for all users.
    if ($select_all) {
      foreach ($selected_options as $name) {
        $context['list'][$name] = $this->getListItem($name);
      }
    }
    else {
      foreach ($removed as $name) {
        unset($context['list'][$name]);
      }
    }

    // Prevent sending emails for all users.
    if (empty($context['list'])) {
      $context['bulk_form_keys'] = [];
    }

    // As context was changed, we need to update the appropriate tempstore.
    $this->setTempstoreData($context, view_id: $context['view_id'], display_id: $context['display_id']);
    parent::setContext($context);
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects): array {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $membership */
    foreach ($objects as $key => $membership) {
      /** @var \Drupal\user\Entity\User $user */
      $user = $membership->getEntity();
      if (!$user instanceof UserInterface) {
        continue;
      }

      if ($this->isUnsubscribedFromEmails($user)) {
        unset($objects[$key]);
      }
    }

    return parent::executeMultiple($objects);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $entity */
    return $entity->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if (!empty($this->context['selected_removed'])) {
      $this->messenger()
        ->addWarning($this->t('Your email will be sent to members who have opted to receive community updates and announcements'));
      $this->messenger()
        ->addWarning($this->formatPlural($this->context['selected_removed'],
          "1 member won't receive this email due to their communication preferences.",
          "@count members won't receive this email due to their communication preferences."
        ));
    }

    // If all selected users have unsubscribed from emails, we should return
    // empty form.
    if (!empty($this->context['selected_removed']) && empty($this->context['list'])) {
      return [
        '#markup' => $this->t('No items selected. Go back and try again.'),
      ];
    }

    // Add title to the form as well.
    if ($form['#title'] !== NULL) {
      $selected_count = $this->context['selected_count'];
      $subtitle = $this->formatPlural($selected_count,
        'Configure the email you want to send to the one member you have selected.',
        'Configure the email you want to send to the @count members you have selected.'
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

  /**
   * {@inheritdoc}
   */
  public function createQueueItem($name, array $data): void {
    $data['bulk_mail_footer'] = TRUE;

    parent::createQueueItem($name, $data);
  }

}
