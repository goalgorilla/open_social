<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\Enum;

/**
 * The available token types.
 *
 * Provides the prefixes for tokens to ensure that we can prevent clients from
 * trying to use the incorrect one for a better DX.
 */
enum TokenType: string {

  // An upload token.
  case Upload = "sfuu";

  // A finalization token.
  case Finalization = "sfuf";

}
