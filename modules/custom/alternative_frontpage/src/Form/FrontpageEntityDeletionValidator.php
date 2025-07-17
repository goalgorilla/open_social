<?php

namespace Drupal\alternative_frontpage\Form;

use Drupal\alternative_frontpage\Service\AvoidDeletingFrontPageEntity;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates forms to prevent deletion of front page entities.
 *
 * This class handles the validation logic for preventing users from deleting
 * entities that are currently set as alternative front pages.
 *
 * @internal
 */
class FrontpageEntityDeletionValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity types that can be set as front pages.
   */
  private const array SUPPORTED_ENTITY_TYPES = [
    'node' => 'getTitle',
    'group' => 'label',
  ];

  /**
   * Front page entity deletion validator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager interface.
   * @param \Drupal\alternative_frontpage\Service\AvoidDeletingFrontPageEntity $avoidDeletingFrontPageEntity
   *   Service to check if the entity is set as an alternative front page.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AvoidDeletingFrontPageEntity $avoidDeletingFrontPageEntity,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('alternative_frontpage.avoid_deleting_frontpage_entity')
    );
  }

  /**
   * Static validation method to check if an entity is set as front page.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateFrontpage(array $form, FormStateInterface $form_state): void {
    $container = \Drupal::getContainer();
    $instance = static::create($container);

    if (isset($form['#form_id']) && $form['#form_id'] === 'alternative_frontpage_delete_form') {
      return;
    }

    $instance->validateFrontpageInternal($form, $form_state);
  }

  /**
   * Static validation method for bulk operations.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateBulkActionFrontpage(array $form, FormStateInterface $form_state): void {
    $container = \Drupal::getContainer();
    $instance = static::create($container);
    $instance->validateBulkActionFrontpageInternal($form, $form_state);
  }

  /**
   * Internal validation method for front page check.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function validateFrontpageInternal(array $form, FormStateInterface $form_state): void {
    $entity = $this->getEntityFromForm($form_state);
    if (!$entity) {
      return;
    }

    $entity_type = $this->getEntityType($entity);
    if (!$entity_type) {
      return;
    }

    if (!in_array($entity->getEntityTypeId(), ['node', 'group'])) {
      return;
    }

    if ($this->avoidDeletingFrontPageEntity->isEntitySetAsAlternativeFrontPage($entity, $entity_type)) {
      $this->setFrontpageDeletionError($form_state, $entity, $entity_type);
    }
  }

  /**
   * Internal validation method for bulk operations.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function validateBulkActionFrontpageInternal(array $form, FormStateInterface $form_state): void {
    $form_storage = $form_state->getStorage();
    $vbo_list = $form_storage['views_bulk_operations']['list'] ?? [];

    foreach ($vbo_list as $item) {
      if (empty($item[0]) || empty($item[2])) {
        continue;
      }

      $entity = $this->loadEntity($item[2], $item[0]);
      if (!$entity) {
        continue;
      }

      // VBO only supports node deletion for bulk operations.
      if ($this->avoidDeletingFrontPageEntity->isEntitySetAsAlternativeFrontPage($entity, 'node')) {
        $this->setFrontpageDeletionError($form_state, $entity, 'node');
        // Stop on the first error to avoid multiple error messages.
        break;
      }
    }
  }

  /**
   * Get the entity from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL if not found.
   */
  private function getEntityFromForm(FormStateInterface $form_state): ?EntityInterface {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EntityFormInterface) {
      return NULL;
    }

    return $form_object->getEntity();
  }

  /**
   * Determine the entity type based on available methods.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return string|null
   *   The entity type or NULL if not supported.
   */
  private function getEntityType(EntityInterface $entity): ?string {
    foreach (self::SUPPORTED_ENTITY_TYPES as $entity_type => $method) {
      if (method_exists($entity, $method)) {
        return $entity_type;
      }
    }

    return NULL;
  }

  /**
   * Load an entity by type and ID.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded entity or NULL if not found.
   */
  private function loadEntity(string $entity_type, int $entity_id): ?EntityInterface {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type);
      return $storage->load($entity_id);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Set the front page deletion error message.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that cannot be deleted.
   * @param string $entity_type
   *   The entity type.
   */
  private function setFrontpageDeletionError(FormStateInterface $form_state, EntityInterface $entity, string $entity_type): void {
    $title = $this->getEntityTitle($entity, $entity_type);
    $form_state->setErrorByName('', $this->t('The entity <strong>@entity</strong> cannot be deleted because it is currently set as a front page in Alternative Front page settings. Please remove it from the <a href="/admin/config/alternative_frontpage">front page settings</a> first.', [
      '@entity' => $title,
    ]));
  }

  /**
   * Get the title of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get the title from.
   * @param string $entity_type
   *   The entity type.
   *
   * @return string
   *   The title of the entity.
   */
  private function getEntityTitle(EntityInterface $entity, string $entity_type): string {
    $method = self::SUPPORTED_ENTITY_TYPES[$entity_type] ?? NULL;
    if (!$method || !method_exists($entity, $method)) {
      return '';
    }

    return (string) $entity->$method();
  }

}
