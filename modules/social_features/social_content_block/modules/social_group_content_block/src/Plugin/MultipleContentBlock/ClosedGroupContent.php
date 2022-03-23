<?php

namespace Drupal\social_group_content_block\Plugin\MultipleContentBlock;

use Drupal\social_content_block\MultipleContentBlockBase;

/**
 * Provides a content block for closed groups.
 *
 * @MultipleContentBlock(
 *   id = "closed_group_content",
 *   label = @Translation("Closed Group"),
 *   entity_type = "group",
 *   bundle = "closed_group"
 * )
 */
class ClosedGroupContent extends MultipleContentBlockBase {

}
