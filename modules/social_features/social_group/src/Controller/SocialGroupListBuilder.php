<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\social_group\GroupStatistics;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for group from GroupListBuilder.
 *
 * @ingroup group
 */
class SocialGroupListBuilder extends EntityListBuilder {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The DateTime formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateTime;

  /**
   * The Group Statistics service.
   *
   * @var \Drupal\social_group\GroupStatistics
   */
  protected $groupStatistics;

  /**
   * Constructs a new GroupListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_time
   *   The datetime formatter service.
   * @param \Drupal\social_group\GroupStatistics $group_statistics
   *   The group statistics service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    RedirectDestinationInterface $redirect_destination,
    DateFormatterInterface $date_time,
    GroupStatistics $group_statistics
  ) {
    parent::__construct($entity_type, $storage);
    $this->redirectDestination = $redirect_destination;
    $this->dateTime = $date_time;
    $this->groupStatistics = $group_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('redirect.destination'),
      $container->get('date.formatter'),
      $container->get('social_group.group_statistics')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'name' => [
        'data' => $this->t('Name'),
        'field' => 'label',
        'specifier' => 'label',
      ],
      'type' => [
        'data' => $this->t('Type'),
        'field' => 'type',
        'specifier' => 'type',
      ],
      'uid' => [
        'data' => $this->t('Creator'),
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'members' => [
        'data' => $this->t('Members'),
      ],
      'created' => [
        'data' => $this->t('Created'),
        'field' => 'created',
        'specifier' => 'created',
        'sort' => 'desc',
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    // EntityListBuilder sets the table rows using the #rows property, so we
    // need to add the render array using the 'data' key.
    $row['name']['data'] = $entity->toLink()->toRenderable();
    $row['type'] = $entity->getGroupType()->label();
    $row['uid'] = $entity->uid->entity->toLink();
    $row['members'] = $this->groupStatistics->getGroupMemberCount($entity);
    $row['created'] = $this->dateTime->format($entity->getCreatedTime(), 'short');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();

    // Add table sorting functionality.
    $headers = $this->buildHeader();
    $query->tableSort($headers);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no groups yet.');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Add the current path or destination as a redirect to the operation links.
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    return $operations;
  }

}
