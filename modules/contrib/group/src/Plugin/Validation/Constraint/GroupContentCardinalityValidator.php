<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\Validation\Constraint\GroupContentCardinalityValidator.
 */

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if content has reached the maximum amount of times it can be added.
 *
 * You should probably only use this constraint in your FieldType plugin through
 * TypedDataInterface::getConstraints() or set it on a base field definition
 * using BaseFieldDefinition->addConstraint('GroupContentCardinality').
 *
 * The reason is that we expect $value to be a FieldItemListInterface and
 * setting the constraint in your FieldType annotation will hand us a single
 * FieldItemInterface object instead. On the other hand, setting it on target_id
 * through BaseFieldDefinition::addPropertyConstraint() will only pass us the
 * integer value (the ID).
 */
class GroupContentCardinalityValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\group\Plugin\Validation\Constraint\GroupContentCardinality $constraint */
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    if (!isset($value)) {
      return;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $value->getEntity();
    $group_cardinality = $group_content->getContentPlugin()->getGroupCardinality();
    $entity_cardinality = $group_content->getContentPlugin()->getEntityCardinality();

    // Exit early if both cardinalities are set to unlimited.
    if ($group_cardinality <= 0 && $entity_cardinality <= 0) {
      return;
    }

    // Only run our checks if an entity was referenced.
    if (!empty($value->target_id)) {
      $entity = $group_content->getEntity();
      $plugin = $group_content->getContentPlugin();
      $plugin_id = $plugin->getPluginId();
      $field_name = $group_content->getFieldDefinition('entity_id')->getLabel();

      // Enforce the group cardinality if it's not set to unlimited.
      if ($group_cardinality > 0) {
        // Get the group content entities for this piece of content.
        $properties = ['type' => $plugin->getContentTypeConfigId(), 'entity_id' => $entity->id()];
        $group_instances = \Drupal::entityTypeManager()
          ->getStorage('group_content')
          ->loadByProperties($properties);

        // Get the groups this content entity already belongs to, not counting
        // the current group towards the limit.
        $group_ids = [];
        foreach ($group_instances as $instance) {
          /** @var \Drupal\group\Entity\GroupContentInterface $instance */
          if ($instance->getGroup()->id() != $group_content->getGroup()->id()) {
            $group_ids[] = $instance->getGroup()->id();
          }
        }
        $group_count = count(array_unique($group_ids));

        // Raise a violation if the content has reached the cardinality limit.
        if ($group_count >= $group_cardinality) {
          $this->context->buildViolation($constraint->groupMessage)
            ->setParameter('@field', $field_name)
            ->setParameter('%content', $entity->label())
            // We need to manually set the path to the first element because we
            // expect this contraint to be set on the EntityReferenceItem level
            // and therefore receive a FieldItemListInterface as the value.
            ->atPath('0')
            ->addViolation();
        }
      }

      // Enforce the entity cardinality if it's not set to unlimited.
      if ($entity_cardinality > 0) {
        // Get the current instances of this content entity in the group.
        $group = $group_content->getGroup();
        $entity_instances = $group->getContentByEntityId($plugin_id, $value->target_id);
        $entity_count = count($entity_instances);

        // If the current group content entity has an ID, exclude that one.
        if ($group_content_id = $group_content->id()) {
          foreach ($entity_instances as $instance) {
            /** @var \Drupal\group\Entity\GroupContentInterface $instance */
            if ($instance->id() == $group_content_id) {
              $entity_count--;
              break;
            }
          }
        }

        // Raise a violation if the content has reached the cardinality limit.
        if ($entity_count >= $entity_cardinality) {
          $this->context->buildViolation($constraint->entityMessage)
            ->setParameter('@field', $field_name)
            ->setParameter('%content', $entity->label())
            ->setParameter('%group', $group->label())
            // We need to manually set the path to the first element because we
            // expect this contraint to be set on the EntityReferenceItem level
            // and therefore receive a FieldItemListInterface as the value.
            ->atPath('0')
            ->addViolation();
        }
      }
    }
  }

}
