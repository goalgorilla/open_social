<?php

namespace Drupal\social_content_block;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base content block implementation.
 *
 * This abstract class provides a method for inserting additional filters to the
 * base query of the "Custom content list block" custom block.
 *
 * @ingroup social_content_block_api
 */
abstract class ContentBlockBase extends PluginBase implements ContentBlockPluginInterface {

}
