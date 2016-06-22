<?php

namespace Drupal\Tests\search_api\Unit\Plugin\Processor;

/**
 * Provides common methods for processor testing unit tests.
 */
trait ProcessorTestTrait {

  /**
   * The tested processor.
   *
   * @var \Drupal\search_api\Processor\ProcessorInterface
   */
  protected $processor;

  /**
   * Invokes a method on the processor.
   *
   * @param string $method_name
   *   The method's name.
   * @param array $args
   *   (optional) The arguments to pass in the method call.
   *
   * @return mixed
   *   Whatever the invoked method returned.
   */
  protected function invokeMethod($method_name, array $args = array()) {
    $class = new \ReflectionClass(get_class($this->processor));
    $method = $class->getMethod($method_name);
    $method->setAccessible(TRUE);
    return $method->invokeArgs($this->processor, $args);
  }

}
