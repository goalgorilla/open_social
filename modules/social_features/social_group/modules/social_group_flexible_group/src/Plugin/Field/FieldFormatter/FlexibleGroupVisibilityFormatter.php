<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_group_flexible_group\Types\GroupVisibility;
use Drupal\social_role_visibility\Service\VisibilityElementManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'flexible_group_visibility' formatter.
 *
 * @FieldFormatter(
 *   id = "flexible_group_visibility",
 *   label = @Translation("Flexible Group Visibility"),
 *   field_types = {
 *     "list_string"
 *   }
 * )
 *
 * @extends \Drupal\Core\Field\FormatterBase<\Drupal\Core\Field\FieldItemListInterface<\Drupal\options\Plugin\Field\FieldType\ListStringItem>>
 */
class FlexibleGroupVisibilityFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a FlexibleGroupVisibilityFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The settings.
   * @param string $label
   *   The formatter label.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\options\Plugin\Field\FieldType\ListStringItem> $items
   *   The field item list.
   * @param string $langcode
   *   The language code.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\options\Plugin\Field\FieldType\ListStringItem $item */
    foreach ($items as $delta => $item) {
      // Use the typed accessor instead of magic property.
      $value = $item->get('value')->getString();

      if ($value === 'visibility_by_role') {
        // Get the group entity and ensure it's a content entity.
        $group_visibility = NULL;
        $entity = $items->getEntity();

        if ($entity instanceof ContentEntityInterface) {
          // Use GroupVisibility class to get role information.
          $group_visibility = GroupVisibility::fromEntity($entity);
        }

        if ($group_visibility && $group_visibility->type === 'roles') {
          $processed_roles = $group_visibility->roles;
          $cm_plus_roles = VisibilityElementManager::CM_PLUS_ROLES;

          if (count(array_intersect($processed_roles, $cm_plus_roles)) === count($cm_plus_roles)) {
            $processed_roles = array_diff($processed_roles, $cm_plus_roles);
            $processed_roles[] = 'CM+';
          }

          $role_labels = [];
          $cm_plus_label = NULL;
          $other_labels = [];

          foreach ($processed_roles as $role) {
            if ($role === 'CM+') {
              $cm_plus_label = 'CM+';
            }
            else {
              $role_entity = $this->entityTypeManager
                ->getStorage('user_role')
                ->load($role);

              $other_labels[] = $role_entity ? $role_entity->label() : $role;
            }
          }

          if ($cm_plus_label) {
            $role_labels[] = $cm_plus_label;
          }

          sort($other_labels);
          foreach ($other_labels as $other_label) {
            $role_labels[] = $other_label;
          }

          $elements[$delta] = [
            '#type' => 'container',
            'title' => [
              '#markup' => $this->t('Visibility by role'),
            ],
            'list' => [
              '#theme' => 'item_list',
              '#items' => $role_labels,
              '#type' => 'ul',
            ],
          ];
        }
        else {
          $elements[$delta] = [
            '#markup' => $this->t('Visibility by role'),
          ];
        }
      }
      else {
        $allowed_values = $items->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getSetting('allowed_values');
        $label = $allowed_values[$value] ?? $value;

        $elements[$delta] = [
          '#markup' => $label,
        ];
      }
    }

    return $elements;
  }

}
