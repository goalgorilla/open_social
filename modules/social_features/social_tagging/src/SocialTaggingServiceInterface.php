<?php

namespace Drupal\social_tagging;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\social_core\Service\MachineNameInterface;

/**
 * Defines the tagging service interface.
 */
interface SocialTaggingServiceInterface {

  /**
   * The default field name.
   */
  public const FIELD = 'social_tagging';

  /**
   * The default name of the wrapper for tags fields.
   */
  public const WRAPPER = 'tagging';

  /**
   * SocialTaggingService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injection of the entityTypeManager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Injection of the configFactory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Injection of the languageManager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\social_core\Service\MachineNameInterface $machine_name
   *   The machine name.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    MachineNameInterface $machine_name
  );

  /**
   * Returns whether the feature is turned on or not.
   */
  public function active(): bool;

  /**
   * Prepares tags field.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $name
   *   The field name.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   (optional) The wrapper title. Defaults to NULL.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The wrapper description. Defaults to NULL.
   * @param string $wrapper
   *   (optional) The wrapper identifier. Defaults to
   *   SocialTaggingServiceInterface::WRAPPER.
   * @param array|null $default_value
   *   (optional) The default value. Defaults to NULL.
   * @param string|null $parent
   *   (optional) The wrapper element name. Defaults to NULL.
   *
   * @return bool
   *   TRUE, if the field is displayed.
   */
  public function field(
    array &$form,
    FormStateInterface $form_state,
    string $name,
    TranslatableMarkup $title = NULL,
    TranslatableMarkup $description = NULL,
    string $wrapper = self::WRAPPER,
    array $default_value = NULL,
    string $parent = NULL
  ): bool;

  /**
   * Returns whether the feature is turned on for groups or not.
   */
  public function groupActive(): bool;

  /**
   * Returns whether the feature is turned on for profiles or not.
   */
  public function profileActive(): bool;

  /**
   * Returns if there are any taxonomy items available.
   */
  public function hasContent(): bool;

  /**
   * Returns whether splitting of fields is allowed.
   *
   * @return bool
   *   Whether category split on field level is turned on or not.
   */
  public function allowSplit(): bool;

  /**
   * Returns the filter query condition.
   *
   * @return string
   *   Returns 'OR' or 'AND'.
   */
  public function queryCondition(): string;

  /**
   * Returns whether using a parent of categories is allowed.
   *
   * @return bool
   *   Whether using categories parent is turned on or not.
   */
  public function useCategoryParent(): bool;

  /**
   * Returns all the top level term items, that are considered categories.
   */
  public function getCategories(): array;

  /**
   * Returns the children of any level term items.
   *
   * @param int $category
   *   The category you want to fetch the child items from.
   */
  public function getChildren(int $category): array;

  /**
   * Returns all the children of top level term items.
   *
   * @return array
   *   An array of child items.
   */
  public function getAllChildren(): array;

  /**
   * Returns a multilevel tree.
   *
   * @param array $term_ids
   *   An array of items that are selected.
   * @param string $entity_type
   *   The entity type these tags are for.
   *
   * @return array
   *   A hierarchy array of items with their parent.
   */
  public function buildHierarchy(array $term_ids, string $entity_type): array;

  /**
   * Gets supported entity types.
   *
   * @param bool $short
   *   (optional) TRUE if entity types should be got only. Defaults to FALSE.
   *
   * @return array
   *   If short mode is enabled then it returns an array of entity type
   *   identifiers, otherwise an associative array of supported entity types.
   *   The keys are entity type identifiers. The values are arrays of bundles.
   */
  public function types(bool $short = FALSE): array;

}
