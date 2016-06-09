<?php

namespace Drupal\dynamic_entity_reference\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;
use Drupal\user\EntityOwnerInterface;

/**
 * Plugin implementation of the 'dynamic_entity_reference autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "dynamic_entity_reference_default",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "dynamic_entity_reference"
 *   }
 * )
 */
class DynamicEntityReferenceWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'match_operator' => 'CONTAINS',
      'size' => '40',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

    $settings = $this->getFieldSettings();
    $labels = \Drupal::entityManager()->getEntityTypeLabels();
    $available = DynamicEntityReferenceItem::getTargetTypes($settings);
    $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();
    $target_type = $items->get($delta)->target_type ?: reset($available);

    $element += array(
      '#type' => 'entity_autocomplete',
      '#target_type' => $target_type,
      '#selection_handler' => $settings[$target_type]['handler'],
      '#selection_settings' => $settings[$target_type]['handler_settings'],
      // Dynamic entity reference field items are handling validation themselves
      // via the 'ValidDynamicReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#element_validate' => array_merge(
        array(array($this, 'elementValidate')),
        \Drupal::service('element_info')->getInfoProperty('entity_autocomplete', '#element_validate', array())
      ),
      '#field_name' => $items->getName(),
    );

    if ($this->getSelectionHandlerSetting('auto_create', $target_type)) {
      $element['#autocreate'] = array(
        'bundle' => $this->getAutocreateBundle($target_type),
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
      );
    }

    $element['#title'] = $this->t('Label');

    $js_class = Html::cleanCssIdentifier("dynamic-entity-reference-{$items->getName()}[$delta][target_type]");
    $entity_type = array(
      '#type' => 'select',
      '#options' => array_intersect_key($labels, array_combine($available, $available)),
      '#title' => $this->t('Entity type'),
      '#default_value' => $target_type,
      '#weight' => -50,
      '#attributes' => array(
        'class' => [
          'dynamic-entity-reference-entity-type',
          $js_class,
        ],
      ),
    );

    $form_element = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
      'target_type' => $entity_type,
      'target_id' => $element,
      '#attached' => array(
        'library' => array(
          'dynamic_entity_reference/drupal.dynamic_entity_reference_widget',
        ),
        'drupalSettings' => array(
          'dynamic_entity_reference' => array(
            $js_class => $this->createAutoCompletePaths($available),
          ),
        ),
      ),
    );
    // Render field as details.
    if ($cardinality == 1) {
      $form_element['#type'] = 'details';
      $form_element['#title'] = $items->getFieldDefinition()->getLabel();
      $form_element['#open'] = TRUE;
    }
    return $form_element;
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidate(&$element, FormStateInterface $form_state, &$form) {
    if (!empty($element['#value'])) {
      // If this is the default value of the field.
      if ($form_state->hasValue('default_value_input')) {
        $values = $form_state->getValue(array(
          'default_value_input',
          $element['#field_name'],
          $element['#delta'],
        ));
      }
      else {
        $values = $form_state->getValue(array(
          $element['#field_name'],
          $element['#delta'],
        ));
      }
      $settings = $this->getFieldSettings();
      $element['#target_type'] = $values['target_type'];
      $element['#selection_handler'] = $settings[$values['target_type']]['handler'];
      $element['#selection_settings'] = $settings[$values['target_type']]['handler_settings'];
      if ($this->getSelectionHandlerSetting('auto_create', $values['target_type'])) {
        $form_object = $form_state->getFormObject();
        $entity = $form_object instanceof EntityFormInterface ? $form_object->getEntity() : '';
        $element['#autocreate'] = array(
          'bundle' => $this->getAutocreateBundle($values['target_type']),
          'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
        );
      }
      else {
        $element['#autocreate'] = NULL;
      }

    }
  }

  /**
   * Returns the value of a setting for the dynamic entity reference handler.
   *
   * @param string $setting_name
   *   The setting name.
   * @param string $target_type
   *   The id of the target entity type.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name, $target_type = NULL) {
    if ($target_type === NULL) {
      return parent::getSelectionHandlerSetting($setting_name);
    }
    $settings = $this->getFieldSettings();
    return isset($settings[$target_type]['handler_settings'][$setting_name]) ? $settings[$target_type]['handler_settings'][$setting_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAutocreateBundle($target_type = NULL) {
    if ($target_type === NULL) {
      return parent::getAutocreateBundle();
    }
    $bundle = NULL;
    if ($this->getSelectionHandlerSetting('auto_create', $target_type)) {
      // If the 'target_bundles' setting is restricted to a single choice, we
      // can use that.
      if (($target_bundles = $this->getSelectionHandlerSetting('target_bundles', $target_type)) && count($target_bundles) == 1) {
        $bundle = reset($target_bundles);
      }
      // Otherwise use the first bundle as a fallback.
      else {
        // @todo Expose a proper UI for choosing the bundle for autocreated
        // entities in https://www.drupal.org/node/2412569.
        $bundles = entity_get_bundles($target_type);
        $bundle = key($bundles);
      }
    }

    return $bundle;
  }

  /**
   * Creates auto complete path for all the given target types.
   *
   * @param string[] $target_types
   *   All the referenceable target types.
   *
   * @return array
   *   Auto complete paths for all the referenceable target types.
   */
  protected function createAutoCompletePaths($target_types) {
    $auto_complete_paths = array();
    $settings = $this->getFieldSettings();
    foreach ($target_types as $target_type) {
      // Store the selection settings in the key/value store and pass a hashed
      // key in the route parameters.
      $selection_settings = $settings[$target_type]['handler_settings'] ?: [];
      $data = serialize($selection_settings) . $target_type . $settings[$target_type]['handler'];
      $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());
      $key_value_storage = \Drupal::keyValue('entity_autocomplete');
      if (!$key_value_storage->has($selection_settings_key)) {
        $key_value_storage->set($selection_settings_key, $selection_settings);
      }
      $auto_complete_paths[$target_type] = Url::fromRoute('system.entity_autocomplete', array(
        'target_type' => $target_type,
        'selection_handler' => $settings[$target_type]['handler'],
        'selection_settings_key' => $selection_settings_key,
      ))->toString();
    }
    return $auto_complete_paths;
  }

}
