<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'social_external_identifier_default_widget' field widget.
 *
 * @FieldWidget(
 *   id = "social_external_identifier_default_widget",
 *   label = @Translation("External Identifier Default"),
 *   field_types = {"social_external_identifier"},
 * )
 */
final class ExternalIdentifierDefaultWidget extends WidgetBase {

  /**
   * Constructs a new ExternalIdentifierDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\social_core\ExternalIdentifierManager\ExternalIdentifierManager $externalIdentifierManager
   *   The external identifier manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected ExternalIdentifierManager $externalIdentifierManager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('social_core.external_identifier_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element['external_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('External ID'),
      '#default_value' => $items[$delta]->external_id ?? NULL,
      '#required' => FALSE,
    ];

    $element['external_owner_target_type'] = [
      '#type' => 'select',
      '#title' => $this->t('External Owner Target Type'),
      '#options' => $this->externalIdentifierManager->getAllowedExternalOwnerTargetTypes(),
      '#empty_value' => '',
      '#default_value' => $items[$delta]->external_owner_target_type ?? NULL,
      '#required' => FALSE,
    ];

    // @todo Ideally use entity_autocomplete, or something like provided by
    //   dynamic_entity_reference. ATM this values will be mostly provided
    //   programmatically, this is why UI/UX is not polished yet. Also
    //   beside consumers entities, there are no other entity types to select
    //   from, but we need to build it in a way that we can support more than
    //   one entity type.
    $element['external_owner_id'] = [
      '#type' => 'number',
      '#title' => $this->t('External Owner'),
      '#default_value' => $items[$delta]->external_owner_id ?? NULL,
      '#min' => 1,
      '#required' => FALSE,
    ];

    return $element;
  }

}
