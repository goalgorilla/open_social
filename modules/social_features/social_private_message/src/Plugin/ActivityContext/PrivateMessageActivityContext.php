<?php

namespace Drupal\social_private_message\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\private_message\Service\PrivateMessageServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'PrivateMessageActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "private_message_activity_context",
 *   label = @Translation("Private message activity context"),
 * )
 */
class PrivateMessageActivityContext extends ActivityContextBase {

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageServiceInterface
   */
  protected $privateMessageService;

  /**
   * PrivateMessageActivityContext constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\Sql\QueryFactory $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\activity_creator\ActivityFactory $activity_factory
   *   The activity factory service.
   * @param \Drupal\private_message\Service\PrivateMessageServiceInterface $private_message_service
   *   The private message service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    PrivateMessageServiceInterface $private_message_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->privateMessageService = $private_message_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query.sql'),
      $container->get('entity_type.manager'),
      $container->get('activity_creator.activity_factory'),
      $container->get('private_message.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_object = $data['related_object'][0];

      if ($related_object['target_type'] === 'private_message') {
        $related_object = $data['related_object'][0];

        if ($related_object['target_type'] === 'private_message') {
          $private_message = $this->entityTypeManager->getStorage('private_message')
            ->load($related_object['target_id']);

          // Must be a Private Message.
          if ($private_message instanceof PrivateMessageInterface) {
            // Get the thread of this message.
            $thread = $this->privateMessageService->getThreadFromMessage($private_message);

            if ($thread instanceof PrivateMessageThreadInterface) {
              // Get all members of this thread.
              /** @var \Drupal\private_message\Entity\PrivateMessageThreadInterface $members */
              $members = $thread->getMembers();

              // Loop over all PMT participants.
              foreach ($members as $member) {
                if ($member instanceof UserInterface) {
                  // Filter out the author of this message.
                  if ($member->id() == $data['actor']) {
                    continue;
                  }

                  // Continue if member have permission to view private message.
                  if (!$member->hasPermission('use private messaging system')) {
                    continue;
                  }

                  // Create the recipients array.
                  $recipients[] = [
                    'target_type' => 'user',
                    'target_id' => $member->id(),
                  ];
                }
              }
            }
          }
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    return $entity->getEntityTypeId() === 'private_message';
  }

}
