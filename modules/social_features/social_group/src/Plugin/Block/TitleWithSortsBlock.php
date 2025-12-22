<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\Plugin\Block\PageTitleBlock;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block with a title and sorts functionality.
 *
 * This block extends the PageTitleBlock and modifies its build method
 * to include additional CSS classes and a library for handling title
 * and sort features. It is primarily used to render a page title with
 * sorting controls included.
 */
#[Block(
  id: "title_with_sorts_block",
  admin_label: new TranslatableMarkup("Title with sorts")
)]
class TitleWithSortsBlock extends PageTitleBlock {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = parent::build();

    $build['#attributes']['class'][] = 'title-with-sorts';
    $build['#attached']['library'][] = 'social_group/move-exposed-sort';

    return $build;
  }

}
