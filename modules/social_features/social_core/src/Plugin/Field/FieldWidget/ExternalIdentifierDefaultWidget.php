<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget\DynamicEntityReferenceWidget;

/**
 * Defines the 'social_external_identifier_default_widget' field widget.
 *
 * @FieldWidget(
 *   id = "social_external_identifier_default_widget",
 *   label = @Translation("External Identifier Default"),
 *   field_types = {"social_external_identifier"},
 * )
 */
final class ExternalIdentifierDefaultWidget extends DynamicEntityReferenceWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state){
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['external_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('External ID'),
      '#default_value' => $items[$delta]->external_id ?? NULL,
      '#required' => FALSE,
    ];
    return $element;
  }

}
