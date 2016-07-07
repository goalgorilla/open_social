<?php

namespace Drupal\social_group\Controller;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for group content from GroupContentListBuilder.
 *
 * @ingroup group
 */
class SocialGroupContentListBuilder extends EntityListBuilder {

  /**
   * The group to show the content for.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The group content types to show in the list.
   *
   * @var string[]
   */
  protected $bundles = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match) {
    parent::__construct($entity_type, $storage);
    $parameters = $route_match->getParameters();

    // Check if the route had a plugin_id parameter.
    if ($parameters->has('plugin_id') && $plugin_ids = (array) $parameters->get('plugin_id')) {
      // We are then able to retrieve the group content type from the group.
      if ($parameters->has('group') && $group = $parameters->get('group')) {
        if ($group instanceof GroupInterface) {
          $this->group = $group;

          // Retrieve the bundles by checking which plugins are enabled.
          $group_type = $group->getGroupType();
          foreach ($plugin_ids as $plugin_id) {
            if ($group_type->hasContentPlugin($plugin_id)) {
              $this->bundles[] = $group_type->getContentPlugin($plugin_id)->getContentTypeConfigId();
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->sort($this->entityType->getKey('id'));
    $query->condition('gid', $this->group->id());

    // Filter on bundles if they were specified by the constructor.
    if (!empty($this->bundles)) {
      $query->condition('type', $this->bundles, 'IN');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['member'] = $this->t('Member');
    $header['organization'] = $this->t('Organisation');
    $header['group_role'] = $this->t('Role');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    // Alter Group Membership table rows.
    if ($entity->getContentPlugin()->getPluginId() == 'group_membership') {
      // Prepare group roles.
      $roles = array();
      foreach ($entity->group_roles->referencedEntities() as $group_role) {
        $roles[] = $group_role->label();
      }
      if (empty($roles)) {
        $roles[] = $this->t('Member');
      }
      $roles = implode(', ', $roles);

      // Get user profile.
      $profile = _social_group_get_member_profile($entity);
      if (!empty($profile)) {
        // EntityListBuilder sets the table rows using the #rows property, so we
        // need to add the render array using the 'data' key.
        $row['member']['data'] = \Drupal::entityTypeManager()
          ->getViewBuilder('profile')
          ->view($profile, 'table');
        $row['organization']['data'] = $profile->get('field_profile_organization')
          ->view(array('label' => 'hidden'));
        $row['group_role'] = $roles;
      }
    }
    else {
      $row['member'] = $entity->id();
      $row['organization']['data'] = $entity->toLink()->toRenderable();
    }
    if (isset($row)) {
      return $row + parent::buildRow($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There is no members yet.');
    return $build;
  }

}
