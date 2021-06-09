<?php

namespace Drupal\social_graphql\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Data definition for the PageInfo data type.
 */
class PageInfoDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $info = &$this->propertyDefinitions;

      $info['hasNextPage'] = DataDefinition::create('boolean')
        ->setRequired(TRUE)
        ->setLabel("Has next page")
        ->setDescription("Whether the result-set has a next page");

      $info['hasPreviousPage'] = DataDefinition::create('boolean')
        ->setRequired(TRUE)
        ->setLabel("Has previous page")
        ->setDescription("Whether the result-set has a previous page");

      $info['startCursor'] = DataDefinition::create('string')
        ->setLabel("Start cursor")
        ->setDescription("The cursor of the first result in the result-set");

      $info['endCursor'] = DataDefinition::create('string')
        ->setLabel("End cursor")
        ->setDescription("The cursor of the last result in the result-set");
    }
    return $this->propertyDefinitions;
  }

}
