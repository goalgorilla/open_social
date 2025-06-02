<?php

namespace Drupal\alternative_frontpage\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\node\NodeForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for validation entity delete.
 *
 * @internal
 */
class AlternativeFrontpageEntityDeleteValidation implements ContainerInjectionInterface {

  /**
   * Alternative frontpage delete validation constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Translation interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager interface.
   */
  public function __construct(
    protected TranslationInterface $translation,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Add a validate function to delete a node form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself. Typically, this is the
   *   name of the function that generated the form.
   *
   * @see hook_form_BASE_FORM_ID_alter()
   */
  #[Alter('form_node_confirm_form')]
  public function addValidateToEntityDeleteForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!str_contains($form_id, 'delete')) {
      return;
    }

    $form['#validate'][] = [$this, 'validateFrontpage'];
  }

  /**
   * Add a validate function to delete a group form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   String representing the name of the form itself. Typically this is the
   *   name of the function that generated the form.
   *
   * @see hook_form_BASE_FORM_ID_alter()
   */
  #[Alter('form_group_confirm_form')]
  public function addValidateToGroupDeleteForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    if (!str_contains($form_id, 'delete')) {
      return;
    }

    $form['#validate'][] = [$this, 'validateFrontpage'];
  }

  /**
   * Add a validate function to a bulk delete form.
  *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see hook_form_FORM_ID_alter()
   */
  #[Alter('form_views_bulk_operations_confirm_action')]
  public function addValidateToBulkDeleteForm(array &$form, FormStateInterface $form_state): void {
    $form_storage = $form_state->getStorage();
    if ($form_storage['views_bulk_operations']['action_id'] !== 'views_bulk_operations_delete_entity') {
      return;
    }

    $form['#validate'][] = [$this, 'validateBulkActionFrontpage'];
  }

  /**
   * Validate whether the deleted entity is set as an alternative frontend.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function validateFrontpage(array $form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    assert($form_object instanceof NodeForm);

    $entity = $form_object->getEntity();

    if ($this->isSetAsAlternativeFrontPage($entity) && method_exists($entity, 'getTitle')) {
      $form_state->setError($form, $this->translation->translate('The @entity is being used as frontpage on Alternative Frontpage settings, remove it to delete.', [
        '@entity' => $entity->getTitle(),
      ]));
    }
  }

  /**
   * Validate if any entities are set as an alternative frontend from bulk op.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function validateBulkActionFrontpage(array $form, FormStateInterface $form_state): void {
    $form_storage = $form_state->getStorage();

    foreach ($form_storage['views_bulk_operations']['list'] as $item) {
      if (empty($item[0]) || empty($item[2])) {
        continue;
      }

      $storage = $this->entityTypeManager->getStorage($item[2]);
      $entity = $storage->load($item[0]);
      if (empty($entity)) {
        continue;
      }

      if ($this->isSetAsAlternativeFrontPage($entity) && method_exists($entity, 'getTitle')) {
        $form_state->setError($form, $this->translation->translate('The @entity is being used as frontpage on Alternative Frontpage settings, remove it to delete.', [
          '@entity' => $entity->getTitle(),
        ]));
      }
    }
  }

  /**
   * Check if the current path of the entity is set as an alternative frontend.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Delete entity to check alternative frontend.
   *
   * @return bool
   *   When TRUE the delete entity is set as an alternative frontend.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function isSetAsAlternativeFrontPage(EntityInterface $entity): bool {
    $storage = $this->entityTypeManager->getStorage('alternative_frontpage');
    $frontpage = $storage->loadByProperties(['path' => $entity->toUrl()->toString()]);

    return count($frontpage) > 0;
  }

}
