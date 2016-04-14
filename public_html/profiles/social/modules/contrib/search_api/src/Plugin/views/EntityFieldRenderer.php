<?php

namespace Drupal\search_api\Plugin\views;

use Drupal\search_api\Plugin\views\field\SearchApiEntityField;
use Drupal\views\Entity\Render\EntityFieldRenderer as ViewsEntityFieldRenderer;
use Drupal\views\Plugin\views\field\FieldHandlerInterface;

/**
 * Renders entity fields.
 *
 * This is used to build render arrays for all entity field values of a view
 * result set sharing the same relationship. An entity translation renderer is
 * used internally to handle entity language properly.
 *
 * Overridden in the Search API since we also need to take the datasource into
 * account, not only the relationship.
 */
class EntityFieldRenderer extends ViewsEntityFieldRenderer {

  /**
   * The datasource ID of this renderer.
   *
   * @var string|null
   */
  protected $datasourceId;

  /**
   * Retrieves the datasource ID.
   *
   * @return string|null
   *   The datasource ID.
   */
  public function getDatasourceId() {
    return $this->datasourceId;
  }

  /**
   * Sets the datasource ID.
   *
   * @param string|null $datasource_id
   *   The new datasource ID.
   *
   * @return $this
   */
  public function setDatasourceId($datasource_id) {
    $this->datasourceId = $datasource_id;
    return $this;
  }

  /**
   * Determines whether this renderer can handle the given field.
   *
   * @param \Drupal\views\Plugin\views\field\FieldHandlerInterface $field
   *   The field for which to check compatibility.
   *
   * @return bool
   *   TRUE if this renderer can handle the given field, FALSE otherwise.
   *
   * @see EntityFieldRenderer::getRenderableFieldIds()
   */
  public function compatibleWithField(FieldHandlerInterface $field) {
    if ($field instanceof SearchApiEntityField && $field->relationship == $this->relationship) {
      // If there is no relationship set, we also need to compare the
      // datasource ID.
      if ($field->relationship || $field->getDatasourceId() == $this->datasourceId) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRenderableFieldIds() {
    $field_ids = [];
    foreach ($this->view->field as $field_id => $field) {
      if ($this->compatibleWithField($field)) {
        $field_ids[] = $field_id;
      }
    }
    return $field_ids;
  }

}
