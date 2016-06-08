<?php

/**
 * @file
 * Contains \Drupal\Message\MessageListBuilder.
 */

namespace Drupal\message;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\message\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Message entities.
 *
 * @see \Drupal\Message\Entity\Message
 */
class MessageListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateService;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_service
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_service) {
    parent::__construct($entity_type, $storage);

    $this->dateService = $date_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'text' => $this->t('Text'),
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'author' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var Message $entity */
    return [
      'changed' => $this->dateService->format($entity->getCreatedTime(), 'short'),
      'text' => $entity->getText(),
      'type' => $entity->getType()->label(),
      'author' => $entity->getOwner()->label(),
    ];
  }

}
