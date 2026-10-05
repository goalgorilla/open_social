<?php

namespace Drupal\social_activity\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\FirstPublicationMarker;
use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_group\CrossPostingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupContentSingleActivityEntityCondition' activity condition.
 *
 * The condition holds when the content is in a single group, or when it is
 * cross-posted and is entering its groups for the first time while a site
 * manager enabled the cross-post notification setting. The plugin id still
 * says "single group" because it is persisted in message template config;
 * renaming it would be a breaking config change.
 *
 * @ActivityEntityCondition(
 *  id = "group_content_node_single_group",
 *  label = @Translation("Entity in single group, or first made visible"),
 *  entities = {"group_content" = {}}
 * )
 */
class GroupContentSingleActivityEntityCondition extends ActivityEntityConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The cross-posting service.
   *
   * @var \Drupal\social_group\CrossPostingService
   */
  protected $crossPostingService;

  /**
   * Constructs a GroupContentSingleActivityEntityCondition object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_group\CrossPostingService $cross_posting_service
   *   The cross-posting service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CrossPostingService $cross_posting_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->crossPostingService = $cross_posting_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_group.cross_posting')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    if (in_array($entity->getEntityTypeId(), ['group_content', 'post'])) {
      // If node is added only to one group then condition is valid.
      if (!$this->crossPostingService->nodeExistsInMultipleGroups($entity)) {
        return TRUE;
      }

      // The content is cross-posted. Notify the members of every group only
      // when a site manager enabled it and the content is being made visible
      // for the first time, not when a later edit adds another group.
      return $this->crossPostingService->notifiesAllGroups()
        && FirstPublicationMarker::isRaised();
    }

    return FALSE;
  }

}
