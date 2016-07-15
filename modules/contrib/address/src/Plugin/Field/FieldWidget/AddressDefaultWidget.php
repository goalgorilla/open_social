<?php

namespace Drupal\address\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Enum\AddressField;
use CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface;
use CommerceGuys\Addressing\Repository\CountryRepositoryInterface;
use CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface;
use Drupal\address\Entity\AddressFormatInterface;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\InitialValuesEvent;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'address' widget.
 *
 * @FieldWidget(
 *   id = "address_default",
 *   label = @Translation("Address"),
 *   field_types = {
 *     "address"
 *   },
 * )
 */
class AddressDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The address format repository.
   *
   * @var \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface
   */
  protected $addressFormatRepository;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Repository\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface
   */
  protected $subdivisionRepository;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The size attributes for fields likely to be inlined.
   *
   * @var array
   */
  protected $sizeAttributes = [
    AddressField::ADMINISTRATIVE_AREA => 30,
    AddressField::LOCALITY => 30,
    AddressField::DEPENDENT_LOCALITY => 30,
    AddressField::POSTAL_CODE => 10,
    AddressField::SORTING_CODE => 10,
  ];

  /**
   * Constructs a AddressDefaultWidget object.
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
   * @param \CommerceGuys\Addressing\Repository\AddressFormatRepositoryInterface $address_format_repository
   *   The address format repository.
   * @param \CommerceGuys\Addressing\Repository\CountryRepositoryInterface $country_repository
   *   The country repository.
   * @param \CommerceGuys\Addressing\Repository\SubdivisionRepositoryInterface $subdivision_repository
   *   The subdivision repository.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AddressFormatRepositoryInterface $address_format_repository, CountryRepositoryInterface $country_repository, SubdivisionRepositoryInterface $subdivision_repository, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->addressFormatRepository = $address_format_repository;
    $this->countryRepository = $country_repository;
    $this->subdivisionRepository = $subdivision_repository;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // @see \Drupal\Core\Field\WidgetPluginManager::createInstance().
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('address.address_format_repository'),
      $container->get('address.country_repository'),
      $container->get('address.subdivision_repository'),
      $container->get('event_dispatcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default_country' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $country_list = $this->countryRepository->getList();
    $element = [];
    $element['default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default country'),
      '#options' => ['site_default' => $this->t('- Site default -')] + $country_list,
      '#default_value' => $this->getSetting('default_country'),
      '#empty_value' => '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $default_country = $this->getSetting('default_country');
    if (empty($default_country)) {
      $default_country = t('None');
    }
    elseif ($default_country == 'site_default') {
      $default_country = t('Site default');
    }
    else {
      $country_list = $this->countryRepository->getList();
      $default_country = $country_list[$default_country];
    }
    $summary = [];
    $summary['default_country'] = $this->t('Default country: @country', ['@country' => $default_country]);

    return $summary;
  }

  /**
   * Gets the initial values for the widget.
   *
   * This is a replacement for the disabled default values functionality.
   *
   * @see address_form_field_config_edit_form_alter()
   *
   * @param array $country_list
   *   The filtered country list, in the country_code => name format.
   *
   * @return array
   *   The initial values, keyed by property.
   */
  protected function getInitialValues(array $country_list) {
    $default_country = $this->getSetting('default_country');
    // Resolve the special site_default option.
    if ($default_country == 'site_default') {
      $default_country = $this->configFactory->get('system.date')->get('country.default');
    }
    // Fallback to the first country in the list if the default country is not
    // available, or is empty even though the field is required.
    $not_available = $default_country && !isset($country_list[$default_country]);
    $empty_but_required = empty($default_country) && $this->fieldDefinition->isRequired();
    if ($not_available || $empty_but_required) {
      $default_country = key($country_list);
    }

    $initial_values = [
      'country_code' => $default_country,
      'administrative_area' => '',
      'locality' => '',
      'dependent_locality' => '',
      'postal_code' => '',
      'sorting_code' => '',
      'address_line1' => '',
      'address_line2' => '',
      'organization' => '',
      'recipient' => '',
    ];
    // Allow other modules to alter the values.
    $event = new InitialValuesEvent($initial_values, $this->fieldDefinition);
    $this->eventDispatcher->dispatch(AddressEvents::INITIAL_VALUES, $event);
    $initial_values = $event->getInitialValues();

    return $initial_values;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $id_prefix = implode('-', array_merge($element['#field_parents'], [$field_name]));
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $item = $items[$delta];
    $full_country_list = $this->countryRepository->getList();
    $country_list = $full_country_list;
    $available_countries = $item->getAvailableCountries();
    if (!empty($available_countries)) {
      $country_list = array_intersect_key($country_list, $available_countries);
    }
    // If the form has been rebuilt via AJAX, use the values from user input.
    // $form_state->getValues() can't be used here because it's empty due to
    // #limit_validaiton_errors.
    $parents = array_merge($element['#field_parents'], [$field_name, $delta]);
    $values = NestedArray::getValue($form_state->getUserInput(), $parents, $has_input);
    if (!$has_input) {
      $values = $item->isEmpty() ? $this->getInitialValues($country_list) : $item->toArray();
    }

    $country_code = $values['country_code'];
    if (!empty($country_code) && !isset($country_list[$country_code])) {
      // This item's country is no longer available. Add it back to the top
      // of the list to ensure all data is displayed properly. The validator
      // can then prevent the save and tell the user to change the country.
      $missingElement = [
        $country_code => $full_country_list[$country_code],
      ];
      $country_list = $missingElement + $country_list;
    }

    // Calling initializeLangcode() every time, and not just when the field
    // is empty, ensures that the langcode can be changed on subsequent
    // edits (because the entity or interface language changed, for example).
    $langcode = $item->initializeLangcode();

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#pre_render' => [
        ['Drupal\Core\Render\Element\Details', 'preRenderDetails'],
        ['Drupal\Core\Render\Element\Details', 'preRenderGroup'],
        [get_class($this), 'groupElements'],
      ],
      '#after_build' => [
        [get_class($this), 'clearValues'],
      ],
      '#attached' => [
        'library' => ['address/form'],
      ],
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ];
    $element['langcode'] = [
      '#type' => 'hidden',
      '#value' => $langcode,
    ];
    // Hide the country dropdown when there is only one possible value.
    if (count($country_list) == 1 && $this->fieldDefinition->isRequired()) {
      $country_code = key($available_countries);
      $element['country_code'] = [
        '#type' => 'hidden',
        '#value' => $country_code,
      ];
    }
    else {
      $element['country_code'] = [
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#options' => $country_list,
        '#default_value' => $country_code,
        '#required' => $element['#required'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#attributes' => [
          'class' => ['country'],
          'autocomplete' => 'country',
        ],
        '#weight' => -100,
      ];
      if (!$element['#required']) {
        $element['country_code']['#empty_value'] = '';
      }
    }
    if (!empty($country_code)) {
      $element = $this->addressElements($element, $values);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return NestedArray::getValue($element, $violation->arrayPropertyPath);
  }

  /**
   * Builds the format-specific address elements.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $values
   *   An array of address values, keyed by property name.
   *
   * @return array
   *   The modified form element array containing the format specific elements.
   */
  protected function addressElements(array $element, array $values) {
    $address_format = $this->addressFormatRepository->get($values['country_code']);
    $required_fields = $address_format->getRequiredFields();
    $labels = LabelHelper::getFieldLabels($address_format);
    foreach ($address_format->getGroupedFields() as $line_index => $line_fields) {
      if (count($line_fields) > 1) {
        // Used by the #pre_render callback to group fields inline.
        $element['container' . $line_index] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['address-container-inline'],
          ],
        ];
      }

      foreach ($line_fields as $field_index => $field) {
        $property = FieldHelper::getPropertyName($field);
        $class = str_replace('_', '-', $property);

        $element[$property] = [
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#default_value' => isset($values[$property]) ? $values[$property] : '',
          '#required' => in_array($field, $required_fields),
          '#size' => isset($this->sizeAttributes[$field]) ? $this->sizeAttributes[$field] : 60,
          '#attributes' => [
            'class' => [$class],
            'autocomplete' => FieldHelper::getAutocompleteAttribute($field),
          ],
        ];
        if (count($line_fields) > 1) {
          $element[$property]['#group'] = $line_index;
        }
      }
    }
    // Hide the label for the second address line.
    if (isset($element['address_line2'])) {
      $element['address_line2']['#title_display'] = 'invisible';
    }
    // Hide fields that have been disabled in the address field settings.
    $enabled_fields = array_filter($this->getFieldSetting('fields'));
    $disabled_fields = array_diff(AddressField::getAll(), $enabled_fields);
    foreach ($disabled_fields as $field) {
      $property = FieldHelper::getPropertyName($field);
      $element[$property]['#access'] = FALSE;
    }
    // Add predefined options to the created subdivision elements.
    $element = $this->processSubdivisionElements($element, $values, $address_format);

    return $element;
  }

  /**
   * Processes the subdivision elements, adding predefined values where found.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $values
   *   An array of address values, keyed by property name.
   * @param \Drupal\address\Entity\AddressFormatInterface $address_format
   *   The address format.
   *
   * @return array
   *   The processed form element array.
   */
  protected function processSubdivisionElements(array $element, array $values, AddressFormatInterface $address_format) {
    $depth = $this->subdivisionRepository->getDepth($values['country_code']);
    if ($depth === 0) {
      // No predefined data found.
      return $element;
    }

    $subdivision_properties = [];
    foreach ($address_format->getUsedSubdivisionFields() as $field) {
      $subdivision_properties[] = FieldHelper::getPropertyName($field);
    }
    // Load and insert the subdivisions for each parent id.
    $currentDepth = 1;
    foreach ($subdivision_properties as $index => $property) {
      if (!isset($element[$property]) || !Element::isVisibleElement($element[$property])) {
        break;
      }
      $parent_property = $index ? $subdivision_properties[$index - 1] : NULL;
      if ($parent_property && empty($values[$parent_property])) {
        break;
      }
      $parent_id = $parent_property ? $values[$parent_property] : NULL;
      $subdivisions = $this->subdivisionRepository->getList($values['country_code'], $parent_id);
      if (empty($subdivisions)) {
        break;
      }

      $element[$property]['#type'] = 'select';
      $element[$property]['#options'] = $subdivisions;
      $element[$property]['#empty_value'] = '';
      unset($element[$property]['#size']);
      if ($currentDepth < $depth) {
        $element[$property]['#ajax'] = [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $element['#wrapper_id'],
        ];
      }

      $currentDepth++;
    }

    return $element;
  }

  /**
   * Groups elements with the same #group so that they can be inlined.
   */
  public static function groupElements(array $element) {
    $sort = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      if (isset($element[$key]['#group'])) {
        // Copy the element to the container and remove the original.
        $group_index = $element[$key]['#group'];
        $container_key = 'container' . $group_index;
        $element[$container_key][$key] = $element[$key];
        unset($element[$key]);
        // Mark the container for sorting.
        if (!in_array($container_key, $sort)) {
          $sort[] = $container_key;
        }
      }
    }
    // Sort the moved elements, so that their #weight stays respected.
    foreach ($sort as $key) {
      uasort($element[$key], ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);
    }

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $country_element = $form_state->getTriggeringElement();
    $address_element = NestedArray::getValue($form, array_slice($country_element['#array_parents'], 0, -1));

    return $address_element;
  }

  /**
   * Clears the country-specific form values when the country changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    $triggering_element_name = end($triggering_element['#parents']);
    if ($triggering_element_name == 'country_code') {
      $keys = [
        'dependent_locality', 'locality', 'administrative_area',
        'postal_code', 'sorting_code',
      ];
      $input = &$form_state->getUserInput();
      foreach ($keys as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

}
