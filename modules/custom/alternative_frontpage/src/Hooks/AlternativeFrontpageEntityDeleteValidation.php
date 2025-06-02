<?php

namespace Drupal\alternative_frontpage\Hooks;

use Drupal\alternative_frontpage\Plugin\Action\ConfirmEntityDeleteAction;
use Drupal\alternative_frontpage\Form\FrontpageEntityDeletionValidator;
use Drupal\alternative_frontpage\Service\AvoidDeletingFrontPageEntity;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;

/**
 * Hooks for validating entity deletion to prevent front page entity deletion.
 *
 * This class provides form alter hooks to add validation that prevents users
 * from deleting entities that are currently set as alternative front pages.
 *
 * @internal
 */
class AlternativeFrontpageEntityDeleteValidation {

  /**
   * Views Bulk Operations delete action ID.
   */
  private const string VBO_DELETE_ACTION = 'views_bulk_operations_delete_entity';

  /**
   * Alternative front page entity delete validation constructor.
   *
   * @param \Drupal\alternative_frontpage\Service\AvoidDeletingFrontPageEntity $avoidDeletingFrontPageEntity
   *   Service to check if the entity is set as an alternative front page.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger factory service.
   */
  public function __construct(
    private readonly AvoidDeletingFrontPageEntity $avoidDeletingFrontPageEntity,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Override 'views_bulk_operations_delete_entity action' to add confirmation.
   *
   * @param array $definitions
   *   Nested array of action definitions.
   */
  #[Alter('action_info')]
  public function infoAlter(array &$definitions): void {
    $definitions[self::VBO_DELETE_ACTION]['class'] = ConfirmEntityDeleteAction::class;
  }

  /**
   * Universal form alter hook to catch ALL delete forms.
   *
   * This hook will be called for every form and will add validation
   * if the form is identified as a delete form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself.
   */
  #[Alter('form')]
  public function addValidateToAllDeleteForms(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Skip if this is not a delete form.
    if (!$this->isDeleteForm($form_id)) {
      return;
    }

    // Skip if this is a VBO form (handled separately).
    if ($this->isVboForm($form_id)) {
      return;
    }

    // Skip if validation is already added.
    if ($this->hasValidation($form)) {
      return;
    }

    // Add validation for entity delete forms.
    if ($this->isEntityDeleteForm($form_state)) {
      $form['#validate'][] = [FrontpageEntityDeletionValidator::class, 'validateFrontpage'];
    }
  }

  /**
   * Add validation to group delete forms.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself.
   */
  #[Alter('form_group_confirm_form')]
  public function addValidateToGroupDeleteForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!$this->isDeleteForm($form_id)) {
      return;
    }

    $form['#validate'][] = [FrontpageEntityDeletionValidator::class, 'validateFrontpage'];
  }

  /**
   * Add validation to the Views Bulk Operations delete forms.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  #[Alter('form_views_bulk_operations_confirm_action')]
  #[Alter('form_views_bulk_operations_configure_action')]
  public function addValidateToBulkDeleteForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->isVboDeleteAction($form_state)) {
      return;
    }

    $form['#validate'][] = [FrontpageEntityDeletionValidator::class, 'validateBulkActionFrontpage'];
  }

  /**
   * Entity pre-delete hook to catch deletions at the entity level.
   *
   * This provides an additional layer of protection for programmatic deletions
   * that might bypass form validation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being deleted.
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    // Only check supported entity types.
    if (!in_array($entity->getEntityTypeId(), ['node', 'group'])) {
      return;
    }

    // Check if the entity is set as a front page.
    if ($this->avoidDeletingFrontPageEntity->isEntitySetAsAlternativeFrontPage($entity, $entity->getEntityTypeId())) {
      // Log the attempt and throw an exception to prevent deletion.
      $this->loggerFactory->get('alternative_frontpage')->warning(
        'Attempted to delete entity @entity (@id) that is set as front page for @type',
        [
          '@entity' => $entity->label(),
          '@id' => $entity->id(),
          '@type' => $entity->getEntityTypeId(),
        ]
      );

      throw new \RuntimeException(
        sprintf(
          'Cannot delete entity "%s" because it is currently set as a front page in Alternative Front page settings.',
          $entity->label()
        )
      );
    }
  }

  /**
   * Check if the form ID indicates a delete operation.
   *
   * @param string $form_id
   *   The form ID to check.
   *
   * @return bool
   *   TRUE if this is a delete form, FALSE otherwise.
   */
  private function isDeleteForm(string $form_id): bool {
    return str_contains($form_id, 'delete');
  }

  /**
   * Check if the form is a VBO form.
   *
   * @param string $form_id
   *   The form ID to check.
   *
   * @return bool
   *   TRUE if this is a VBO form, FALSE otherwise.
   */
  private function isVboForm(string $form_id): bool {
    return str_contains($form_id, 'views_bulk_operations');
  }

  /**
   * Check if the VBO action is a delete operation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if this is a VBO delete action, FALSE otherwise.
   */
  private function isVboDeleteAction(FormStateInterface $form_state): bool {
    $form_storage = $form_state->getStorage();
    return isset($form_storage['views_bulk_operations']['action_id']) &&
           $form_storage['views_bulk_operations']['action_id'] === self::VBO_DELETE_ACTION;
  }

  /**
   * Check if the form is an entity delete form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if this is an entity delete form, FALSE otherwise.
   */
  private function isEntityDeleteForm(FormStateInterface $form_state): bool {
    $form_object = $form_state->getFormObject();

    if ($form_object instanceof EntityFormInterface === FALSE) {
      return FALSE;
    }

    // Check if the form object class name indicates a delete form.
    $class_name = get_class($form_object);
    return str_contains($class_name, 'DeleteForm') ||
      str_contains($class_name, 'ConfirmForm');
  }

  /**
   * Check if validation is already added to the form.
   *
   * @param array $form
   *   The form array.
   *
   * @return bool
   *   TRUE if validation is already added, FALSE otherwise.
   */
  private function hasValidation(array $form): bool {
    if (!isset($form['#validate'])) {
      return FALSE;
    }

    foreach ($form['#validate'] as $validator) {
      if (is_array($validator) &&
          is_string($validator[0]) &&
          $validator[0] === FrontpageEntityDeletionValidator::class) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
