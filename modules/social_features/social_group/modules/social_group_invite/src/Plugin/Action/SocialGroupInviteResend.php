<?php

namespace Drupal\social_group_invite\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resend invites for group members.
 *
 *  This action allows to resend membership invitations to users. The logic
 *  is very simple - remove old invitation and create new one. Creating a new
 *  invitation (a group content entity) triggers email sending with appropriate
 *  text. Site manager is able to change this text in global group settings
 *  page.
 *
 * @Action(
 *   id = "social_group_invite_resend_action",
 *   label = @Translation("Resend invites for group members"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class SocialGroupInviteResend extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * Temp store key with resent invitations.
   *
   * @var string
   */
  const TEMP_STORE_ID = 'resent_invite_ids';

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Stores the shared tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TimeInterface $time, SharedTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->time = $time;
    $this->tempStore = $temp_store_factory->get('social_group_invite');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('datetime.time'),
      $container->get('tempstore.shared'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function execute($entity = NULL): void {
    // This action allows to resend invitations for each member.
    $time = $this->time->getCurrentTime();

    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $duplicate = $entity->createDuplicate();
    // We can leave the "created" field value without changes.
    // But we want to "bring up" our resent invitation to the top of
    // the user's invitations list as newly created invitations are placing
    // higher than old ones.
    $duplicate->set('created', $time);
    $duplicate->setChangedTime($time);

    // Set new invite to temp storage. This needs to identify if recipient of
    // this invite should receive the altered invite message.
    // Unfortunately, sending emails is triggering on entity inserting,
    // and we don't have an "id" yet, so we should "uuid" property.
    /* @see ginvite_group_content_insert() */
    $values = (array) $this->tempStore->get(self::TEMP_STORE_ID);
    $values[$duplicate->uuid()] = $duplicate->uuid();
    $this->tempStore->set(self::TEMP_STORE_ID, $values);

    $duplicate->save();

    // Remove original invitation as don't needed.
    $entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $object->access('delete', $account, TRUE);
    return $return_as_object ? $access : $access->isAllowed();
  }

}
