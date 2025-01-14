<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Pre-processes variables for the "data_policy_data_policy" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("data_policy_data_policy")
 */
class DataPolicyDataPolicy extends PreprocessBase implements ContainerFactoryPluginInterface {

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    Request $request
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function preprocessVariables(Variables $variables): void {
    if (!$this->request->request->has('js')) {
      $variables->attributes['class'][] = 'card__body';
    }
  }

}
