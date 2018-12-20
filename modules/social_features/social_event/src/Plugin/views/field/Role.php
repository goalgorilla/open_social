<?php

namespace Drupal\social_event\Plugin\views\field;

use Drupal\Component\Utility\Xss;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;

/**
 * Field handler to show a role of an enrollment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("event_enrollment_role")
 */
class Role extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $label = $this->t('Enrollee');

    $this->getModuleHandler()->alter('social_event_role', $label, $values);

    return ViewsRenderPipelineMarkup::create(Xss::filterAdmin($label));
  }

}
