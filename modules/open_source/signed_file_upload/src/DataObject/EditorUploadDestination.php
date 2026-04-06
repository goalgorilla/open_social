<?php

declare(strict_types=1);

namespace Drupal\signed_file_upload\DataObject;

/**
 * A file that will be used in a Drupal rich text editor.
 */
readonly class EditorUploadDestination implements UploadDestinationInterface {

  /**
   * Create a new editor upload destination.
   *
   * @param non-empty-string $textFormatId
   *   The ID of the text format this will be used in (e.g. 'basic_html').
   * @param non-empty-string $editorId
   *   The ID of the editor configuration with which this will be evaluated.
   */
  public function __construct(
    public string $textFormatId,
    public string $editorId,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function equals(mixed $other) : bool {
    if (!$other instanceof self) {
      return FALSE;
    }

    return $this->textFormatId === $other->textFormatId
      && $this->editorId === $other->editorId;
  }

}
