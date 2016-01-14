<?php

/**
 * @file
 * Contains \Drupal\kint\Twig\KintExtension.
 */

namespace Drupal\kint\Twig;

/**
 * Provides the Kint debugging function within Twig templates.
 */
class KintExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'kint';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('kint', array($this, 'kint'), array(
        'is_safe' => array('html'),
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
      )),
    );
  }

  /**
   * Provides Kint function to Twig templates.
   *
   * Handles 0, 1, or multiple arguments.
   *
   * Code derived from https://github.com/barelon/CgKintBundle.
   *
   * @param Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   */
  public function kint(\Twig_Environment $env, array $context) {
    // Don't do anything unless twig_debug is enabled. This reads from the Twig
    // environment, not Drupal Settings, so a container rebuild is necessary
    // when toggling twig_debug on and off. We can consider injecting Settings.
    if (!$env->isDebug()) {
      return;
    }
    kint_require();
    // Don't display where Kint was called from.
    // @todo Can we add information about which template Kint was called from?
    \Kint::$displayCalledFrom = FALSE;

    $output = '';

    if (func_num_args() === 2) {
      // No arguments passed to kint(), display full Twig context.
      $kint_variable = array();
      foreach ($context as $key => $value) {
        if (!$value instanceof \Twig_Template) {
          $kint_variable[$key] = $value;
        }
      }

      $result = @\Kint::dump($kint_variable);
      $output = str_replace('$kint_variable', 'Twig context', $result);
    }
    else {
      // Try to get the names of variables from the Twig template.
      $trace = debug_backtrace();
      $callee = $trace[0];

      $lines = file($callee['file']);
      $source = $lines[$callee['line'] - 1];

      preg_match('/kint\((.+)\);/', $source, $matches);
      $parameters = $matches[1];
      $parameters = preg_replace('/\$this->getContext\(\$context, "(.+)"\)/U', "$1", $parameters);
      $parameters = preg_replace('/\(isset\(\$context\["(.+)"\]\) \? \$context\["(.+)"\] : null\)/U', "$1", $parameters);
      do {
        $parameters = preg_replace('/\$this->getAttribute\((.+), "(.+)"\)/U', "$1.$2", $parameters, 1, $found);
      } while ($found);

      $parameters = explode(', ', $parameters);
      foreach ($parameters as $index => $parameter) {
        // Remove bad entries from the parameters array. Maybe we can avoid this
        // by doing more with the regular expressions.
        // This only seems to be needed for cases like:
        // {{ my_array['#hash_index'] }}
        if (in_array($parameter, array('array()', '"array'))) {
          unset($parameters[$index]);
          continue;
        }
        // Trim parens and quotes from the parameter strings.
        $parameters[$index] = trim($parameter, '()"');
      }

      // Don't include $env and $context arguments in $args and $parameters.
      $args = array_slice(func_get_args(), 2);
      $parameters = array_slice($parameters, 2);

      // If there is only one argument, pass to Kint without too much hassle.
      if (count($args) == 1) {
        $kint_variable = reset($args);
        $result = @\Kint::dump($kint_variable);
        // Replace $kint_variable with the name of the variable in the Twig
        // template.
        $output = str_replace('$kint_variable', reset($parameters), $result);
      }
      else {
        // Build an array of variable to pass to Kint.
        // @todo Can we just call_user_func_array while still retaining the
        //   variable names?
        foreach ($args as $index => $arg) {
          // Prepend a unique index to allow debugging the same variable more
          // than once in the same Kint dump.
          $kint_args['_index_' . $index . '_' . $parameters[$index]] = $arg;
        }

        $result = @\Kint::dump($kint_args);
        // Display a comma separated list of the variables contained in this group.
        $output = str_replace('$kint_args', implode(', ', $parameters), $result);
        // Remove unique indexes from output.
        $output = preg_replace('/_index_([0-9]+)_/', '', $output);
      }
    }

    return $output;
  }

}
