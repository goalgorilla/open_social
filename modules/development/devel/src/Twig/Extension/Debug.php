<?php

namespace Drupal\devel\Twig\Extension;

use Drupal\devel\DevelDumperManagerInterface;

/**
 * Provides the Devel debugging function within Twig templates.
 *
 * NOTE: This extension doesn't do anything unless twig_debug is enabled.
 * The twig_debug setting is read from the Twig environment, not Drupal
 * Settings, so a container rebuild is necessary when toggling twig_debug on
 * and off.
 */
class Debug extends \Twig_Extension {

  /**
   * The devel dumper service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * Constructs a Debug object.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The devel dumper service.
   */
  public function __construct(DevelDumperManagerInterface $dumper) {
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'devel_debug';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $functions = [];

    foreach (['devel_dump', 'kpr'] as $function) {
      $functions[] = new \Twig_SimpleFunction($function, [$this, 'dump'], [
        'is_safe' => ['html'],
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
        'is_variadic' => TRUE,
      ]);
    }

    foreach (['devel_message', 'dpm', 'dsm'] as $function) {
      $functions[] = new \Twig_SimpleFunction($function, [$this, 'message'], [
        'is_safe' => ['html'],
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
        'is_variadic' => TRUE,
      ]);
    }

    return $functions;
  }

  /**
   * Provides debug function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @return string
   *   String representation of the input variables.
   *
   * @see \Drupal\devel\DevelDumperManager::dump()
   */
  public function dump(\Twig_Environment $env, array $context, array $args = []) {
    if (!$env->isDebug()) {
      return;
    }

    ob_start();

    // No arguments passed, display full Twig context.
    if (empty($args)) {
      $context_variables = $this->getContextVariables($context);
      $this->dumper->dump($context_variables, 'Twig context');
    }
    else {
      foreach ($args as $variable) {
        $this->dumper->dump($variable);
      }
    }

    return ob_get_clean();
  }

  /**
   * Provides debug function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * @param \Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @return void
   *
   * @see \Drupal\devel\DevelDumperManager::message()
   */
  public function message(\Twig_Environment $env, array $context, array $args = []) {
    if (!$env->isDebug()) {
      return;
    }

    // No arguments passed, display full Twig context.
    if (empty($args)) {
      $context_variables = $this->getContextVariables($context);
      $this->dumper->message($context_variables, 'Twig context');
    }
    else {
      foreach ($args as $variable) {
        $this->dumper->message($variable);
      }
    }

  }

  /**
   * Filters the Twig context variable.
   *
   * @param array $context
   *  The Twig context.
   *
   * @return array
   *   An array Twig context variables.
   */
  protected function getContextVariables(array $context) {
    $context_variables = [];
    foreach ($context as $key => $value) {
      if (!$value instanceof \Twig_Template) {
        $context_variables[$key] = $value;
      }
    }
    return $context_variables;
  }

}
