<?php

namespace Drupal\social_group_content_block\Plugin\MultipleContentBlock;

use Drupal\social_content_block\MultipleContentBlockBase;

/**
 * Provides a content block for public groups.
 *
 * @MultipleContentBlock(
 *   id = "public_group_content",
 *   label = @Translation("Public Group"),
 *   entity_type = "group",
 *   bundle = "public_group"
 * )
 */
class PublicGroupContent extends MultipleContentBlockBase {

}
