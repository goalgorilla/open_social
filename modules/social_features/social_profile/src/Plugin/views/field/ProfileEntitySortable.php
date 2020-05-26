<?php

namespace Drupal\social_profile\Plugin\views\field;

use Drupal\views\Plugin\views\field\RenderedEntity;

/**
 * Field handler to sort rendered profile entity in views.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("profile_entity_sortable")
 */
class ProfileEntitySortable extends RenderedEntity {

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    if (isset($this->field_alias)) {
      // If we want to sort on the profile name, add the correct alias.
      if ($this->table === 'profile' && $this->field === 'profile_entity_sortable') {
        $this->field_alias = $this->view->relationship['profile']->tableAlias . '.name';
      }
      // Since fields should always have themselves already added, just
      // add a sort on the field.
      $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
      $this->query->addOrderBy(NULL, NULL, $order, $this->field_alias, $params);
    }
  }

}
