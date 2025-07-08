<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Command {

  public function __construct(
    public readonly string $name,
  ) {}

}
