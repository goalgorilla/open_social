<?php

declare(strict_types=1);

namespace Drupal\group_core_comments\Access;

use Drupal\social_comment\HiddenCommentFieldAccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves hidden comment field access when social_comment may be absent.
 *
 * The group_core_comments module registers a kernel fallback for
 * HiddenCommentFieldAccessInterface. When social_comment is enabled,
 * callers must use its service directly so hidden-field rules are not
 * bypassed.
 */
final class HiddenCommentFieldAccessLocator {

  /**
   * Prevent instantiation.
   */
  private function __construct() {}

  /**
   * Returns the hidden comment field access service.
   */
  public static function get(ContainerInterface $container): HiddenCommentFieldAccessInterface {
    if ($container->get('module_handler')->moduleExists('social_comment')) {
      return $container->get('social_comment.hidden_comment_field_access');
    }

    return $container->get(HiddenCommentFieldAccessInterface::class);
  }

}
