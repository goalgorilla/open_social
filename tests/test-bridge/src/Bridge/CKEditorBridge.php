<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Config\ConfigFactoryInterface;
use OpenSocial\TestBridge\Attributes\Command;
use Psr\Container\ContainerInterface;

class CKEditorBridge {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
    );
  }

  /**
   * Removes all CKEditor configs.
   *
   * @return string[]
   *   The result.
   */
  #[Command(name: "disable-ckeditor")]
  public function disableCkEditor() : array {
    $this->configFactory->getEditable('editor.editor.basic_html')->delete();
    $this->configFactory->getEditable('editor.editor.full_html')->delete();

    return ["status" => "ok"];
  }

}
