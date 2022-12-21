<?php

declare(strict_types=1);

namespace Drupal\social\Behat\Chrome;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Rewrites DMore/../ChromeFactory to load our adapted driver.
 *
 * Unfortunately we must copy and keep in sync the entire factory since the
 * class is marked as final.
 *
 * If we can find an easy way to adapt `buildDriver` and change the class of
 * the definition provided to
 * \Behat\MinkExtension\ServiceContainer\MinkExtension then we can remove this
 * class.
 */
final class ChromeFactory implements DriverFactory {

  /**
   * {@inheritdoc}
   */
  public function getDriverName() {
    return 'chrome';
  }

  /**
   * {@inheritdoc}
   */
  public function configure(ArrayNodeDefinition $builder) {
    $builder->children()
      ->scalarNode('api_url')->end()
      ->booleanNode('validate_certificate')->defaultTrue()->end()
      ->enumNode('download_behavior')
      ->values(['allow', 'default', 'deny'])->defaultValue('default')->end()
      ->scalarNode('download_path')->defaultValue('/tmp')->end()
      ->integerNode('socket_timeout')->defaultValue(10)->end()
      ->integerNode('dom_wait_timeout')->defaultValue(3000)->end()
      ->end();
  }

  /**
   * {@inheritdoc}
   */
  public function buildDriver(array $config) {
    $validateCert = isset($config['validate_certificate']) ? $config['validate_certificate'] : TRUE;
    $socketTimeout = $config['socket_timeout'];
    $domWaitTimeout = $config['dom_wait_timeout'];
    $downloadBehavior = $config['download_behavior'];
    $downloadPath = $config['download_path'];
    return new Definition(ChromeDriver::class, [
      $this->resolveApiUrl($config['api_url']),
      NULL,
      '%mink.base_url%',
      [
        'validateCertificate' => $validateCert,
        'socketTimeout' => $socketTimeout,
        'domWaitTimeout' => $domWaitTimeout,
        'downloadBehavior' => $downloadBehavior,
        'downloadPath' => $downloadPath,
      ]
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsJavascript() {
    return TRUE;
  }

  private function resolveApiUrl($url) {
    $host = parse_url($url, PHP_URL_HOST);

    if (filter_var($host, FILTER_VALIDATE_IP)) {
      return $url;
    }

    return str_replace($host, gethostbyname($host), $url);
  }
}
