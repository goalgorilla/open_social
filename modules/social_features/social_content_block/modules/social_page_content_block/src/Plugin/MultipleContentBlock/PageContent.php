<?php

namespace Drupal\social_page_content_block\Plugin\MultipleContentBlock;

use Drupal\social_content_block\MultipleContentBlockBase;

/**
 * Provides a content block for basic pages.
 *
 * @MultipleContentBlock(
 *   id = "pages_content",
 *   label = @Translation("Basic Page"),
 *   entity_type = "node",
 *   bundle = "page"
 * )
 */
class PageContent extends MultipleContentBlockBase {

}
