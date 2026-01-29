<?php

declare(strict_types=1);

namespace Drupal\social_language\Hooks;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\hux\Attribute\Alter;

/**
 * Specific form altering hooks for the module.
 */
final class SocialLanguageFormAlter {

  /**
   * Initializes an instance of the SocialLanguageFormAlter object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct(
    protected readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Hide translation elements from group form.
   *
   * @param array $form
   *   The form structure that is to be altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  #[Alter('form_group_form')]
  public function hideGroupTranslationElements(array &$form, FormStateInterface $form_state): void {
    // For groups, we want to have the same approach as for nodes.
    /* @see \Drupal\node\NodeTranslationHandler::entityFormAlter() */
    if (isset($form['content_translation'])) {
      // We do not need to show these values on node forms: they inherit the
      // basic group property values.
      /* @see static::groupFormEntityBuild() */
      $form['content_translation']['status']['#access'] = FALSE;
      $form['content_translation']['name']['#access'] = FALSE;
      $form['content_translation']['created']['#access'] = FALSE;

      // Process the submitted values before they are stored.
      $form['#entity_builders'][] = [$this, 'groupFormEntityBuild'];
    }
  }

  /**
   * Alters the form entity build process for group entities.
   *
   * @param string $entity_type
   *   The type of entity being processed.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being built in the form.
   * @param array $form
   *   The form structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function groupFormEntityBuild(string $entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    if ($entity_type !== 'group') {
      return;
    }

    if (!$form_state->hasValue('content_translation')) {
      return;
    }

    assert($entity instanceof GroupInterface);

    $translation = &$form_state->getValue('content_translation');
    $translation['status'] = (bool) $form_state->getValue(['status', 'value']);
    $translation['uid'] = $entity->getOwnerId() ?: 0;
    $translation['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'custom', 'Y-m-d H:i:s O');
  }

}
