<?php

declare(strict_types=1);

namespace Drupal\social_eda\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a BackfillHandler plugin annotation object.
 *
 * @see \Drupal\social_eda\Plugin\BackfillHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
final class BackfillHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public Translation $label;

  /**
   * The entity type (e.g., 'node', 'user', 'comment').
   *
   * @var string
   */
  public string $entity_type;

  /**
   * The bundle (e.g., 'topic', 'post', 'comment').
   *
   * @var string
   */
  public string $bundle;

  /**
   * The service ID of the EdaHandler to use.
   *
   * @var string
   */
  public string $handler_service;

  /**
   * The method name to call on the handler (e.g., 'topicCreate').
   *
   * @var string
   */
  public string $handler_method;

}
