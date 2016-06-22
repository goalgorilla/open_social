<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileListController.
 */

namespace Drupal\profile;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List controller for profiles.
 *
 * @see \Drupal\profile\Entity\Profile
 */
class ProfileListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ProfileListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_formatter, RendererInterface $renderer) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'owner' => [
        'data' => $this->t('Owner'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => $this->t('Status'),
      'is_default' => $this->t('Default'),
      'changed' => [
        'data' => $this->t('Updated'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $langcode = $entity->language()->getId();
    $uri = $entity->toUrl();
    $options = $uri->getOptions();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? ['language' => $languages[$langcode]] : []);
    $uri->setOptions($options);
    $row['label'] = $entity->link();
    $row['type'] = $entity->getType();
    $row['owner']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['status'] = $entity->isActive() ? $this->t('active') : $this->t('not active');
    $row['is_default'] = $entity->isDefault() ? $this->t('default') : $this->t('not default');
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    $language_manager = \Drupal::languageManager();
    if ($language_manager->isMultilingual()) {
      $row['language_name'] = $language_manager->getLanguageName($langcode);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (!$entity->isDefault()) {
      $operations['set_default'] = [
        'title' => $this->t('Mark as default'),
        'url' => $entity->toUrl('set-default'),
        'parameter' => $entity,
      ];
    }

    return $operations;
  }

}
