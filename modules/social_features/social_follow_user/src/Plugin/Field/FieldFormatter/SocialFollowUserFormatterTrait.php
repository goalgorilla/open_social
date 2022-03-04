<?php

namespace Drupal\social_follow_user\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides methods for adding a link to entity author to field formatter.
 */
trait SocialFollowUserFormatterTrait {

  /**
   * Check if the formatter settings form can be extended.
   */
  public function isOwnerForm(): bool {
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();

    if ($entity_type_id !== 'user') {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

      if (
        $entity_type !== NULL &&
        is_subclass_of($entity_type->getClass(), EntityOwnerInterface::class) &&
        $entity_type->hasKey('owner')
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Extend formatter summary.
   *
   * @param array $summary
   *   The original short summary of the formatter settings.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $text
   *   The message shows that the link to the author is enabled.
   */
  public function alterOwnerSummary(array $summary, TranslatableMarkup $text): array {
    if ($this->isOwnerReady()) {
      $summary[] = $text;
    }

    return $summary;
  }

  /**
   * Returns the author's URL if the corresponding option is enabled.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   */
  public function getOwnerUrl(FieldItemListInterface $items): ?Url {
    if (!$items->isEmpty() && $this->isOwnerReady()) {
      $entity = $items->getEntity();

      if (!$entity->isNew() && $entity instanceof EntityOwnerInterface) {
        return $entity->getOwner()->toUrl();
      }
    }

    return NULL;
  }

  /**
   * Check if an option for extending the formatter is enabled.
   */
  private function isOwnerReady(): bool {
    return $this->getSetting(self::OWNER_KEY) === self::OWNER_VALUE;
  }

}
