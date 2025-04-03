<?php

namespace Drupal\grequest\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;

/**
 * Group relationship base form.
 *
 * @ingroup group
 */
class GroupRelationshipBaseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return str_replace('-', '_', parent::getFormId());
  }

  /**
   * Returns the plugin responsible for this piece of group relationship.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The responsible group relation.
   */
  protected function getPlugin() {
    /** @var \Drupal\group\Entity\GroupRelationInterface $group_relationship */
    $group_relationship = $this->getEntity();
    return $group_relationship->getPlugin();
  }

}
