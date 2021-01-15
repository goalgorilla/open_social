<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\socialbase\Plugin\Preprocess\Field as FieldBase;

/**
 * Pre-processes variables for the "field" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("field")
 */
class Field extends FieldBase {

  /**
   * {@inheritdoc}
   */
  protected $wrapperClass = 'card--content-merged__list';

}
