<?php

namespace Drupal\webprofiler\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\webprofiler\Profiler\Profiler;
use Symfony\Component\Routing\Route;

/**
 * Class TokenConverter
 */
class TokenConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\webprofiler\Profiler\Profiler
   */
  private $profiler;

  /**
   * Constructs a new WebprofilerController.
   *
   * @param \Drupal\webprofiler\Profiler\Profiler $profiler
   */
  public function __construct(Profiler $profiler) {
    $this->profiler = $profiler;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (NULL === $this->profiler) {
      return NULL;
    }

    $profile = $this->profiler->loadProfile($value);

    if (NULL === $profile) {
      return NULL;
    }

    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] === 'webprofiler:token') {
      return TRUE;
    }
    return FALSE;
  }
}
