<?php

namespace Drupal\social_follow_taxonomy\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'FollowTaxonomyActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *  id = "follow_taxonomy_activity_context",
 *  label = @Translation("Following taxonomy activity context"),
 * )
 */
class FollowTaxonomyActivityContext extends ActivityContextBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ActivityContextBase constructor.
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    // It could happen that a notification has been queued but the account has
    // since been deleted and message author is anonymous.
    if (!empty($data['actor']) && $data['actor'] === 0) {
      return [];
    }

    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);

      if ($related_entity['target_type'] == 'node' || $related_entity['target_type'] == 'post') {
        $recipients += $this->getRecipientsWhoFollowTaxonomy($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * List of taxonomy terms.
   */
  public function taxonomyTermsList($entity) {
    $term_ids = social_follow_taxonomy_terms_list($entity);

    return $term_ids;
  }

  /**
   * Returns recipients from followed taxonomies.
   */
  public function getRecipientsWhoFollowTaxonomy(array $related_entity, array $data) {
    $recipients = [];

    $entity = $this->entityTypeManager->getStorage($related_entity['target_type'])
      ->load($related_entity['target_id']);

    if (!empty($entity)) {
      $tids = $this->taxonomyTermsList($entity);
    }

    if (empty($tids)) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('flagging');
    $flaggings = $storage->loadByProperties([
      'flag_id' => 'follow_term',
      'entity_type' => 'taxonomy_term',
      'entity_id' => $tids,
    ]);

    foreach ($flaggings as $flagging) {
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      $recipient = $flagging->getOwner();

      // It could happen that a notification has been queued but the content or
      // account has since been deleted. In that case we can find no recipient.
      if (!$recipient instanceof UserInterface) {
        continue;
      }

      // Do not send notification for inactive user.
      if (
        $recipient->isBlocked() ||
        !$recipient->getLastLoginTime()
      ) {
        continue;
      }

      // We don't send notifications to content creator.
      if ($recipient->id() !== $entity->getOwnerId()) {
        if (!in_array($recipient->id(), array_column($recipients, 'target_id'))) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $recipient->id(),
          ];
        }
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    if (!$entity instanceof ContentEntityInterface) {
      return FALSE;
    }

    // Check entity type.
    switch ($entity->getEntityTypeId()) {
      case 'node':
        foreach ($this->getListOfTagsFields() as $field_name) {
          if ($entity->hasField($field_name)) {
            return TRUE;
          }
        }
        return FALSE;
    }
    return FALSE;
  }

  /**
   * Returns list of field names that needs to check for entity validation.
   *
   * @return string[]
   *   List of filed names.
   */
  public function getListOfTagsFields() {
    $fields_to_check = [
      'social_tagging',
    ];
    $this->moduleHandler->alter('social_follow_taxonomy_fields', $fields_to_check);
    return $fields_to_check;
  }

}
