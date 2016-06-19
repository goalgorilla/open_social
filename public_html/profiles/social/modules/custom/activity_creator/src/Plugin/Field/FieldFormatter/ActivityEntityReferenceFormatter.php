<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\Field\FieldFormatter\ActivityEntityReferenceFormatter.
 */

namespace Drupal\activity_creator\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceEntityFormatter;

/**
 * Provides a custom dynamic entity reference formatter.
 *
 * @FieldFormatter(
 *   id = "activity_creator_entity_reference_formatter",
 *   module = "activity_creator",
 *   label = @Translation("Custom dynamic entity reference formatter for activities"),
 *   field_types = {
 *     "dynamic_entity_reference"
 *   }
 * )
 */
class ActivityEntityReferenceFormatter extends DynamicEntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', array('@entity_type' => $entity->getEntityTypeId(), '@entity_id' => $entity->id()));
        return $elements;
      }

      if ($entity->id()) {
        $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());

        // Add a resource attribute to set the mapping property's value to the
        // entity's url. Since we don't know what the markup of the entity will
        // be, we shouldn't rely on it for structured data such as RDFa.
        if (!empty($items[$delta]->_attributes)) {
          $items[$delta]->_attributes += array('resource' => $entity->url());
        }
      }
      else {
        // This is an "auto_create" item.
        $elements[$delta] = array('#markup' => $entity->label());
      }
      $depth = 0;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = $this->entityDisplayRepository->getAllViewModes();
    $only_view_modes = array();
    foreach ($options as $entity) {
      foreach ($entity as $key => $view_mode) {
        $only_view_modes[$key] = $view_mode['label'];
      }
    }

    $elements['view_mode'] = array(
      '#type' => 'select',
      '#options' => $only_view_modes,
      '#title' => t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    );

    return $elements;
  }
}
