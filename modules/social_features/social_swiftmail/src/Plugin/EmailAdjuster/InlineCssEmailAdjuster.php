<?php

namespace Drupal\social_swiftmail\Plugin\EmailAdjuster;

use Drupal\Core\Asset\AssetOptimizerInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailAdjusterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Defines the Inline CSS Email Adjuster.
 *
 * @EmailAdjuster(
 *   id = "mailer_inline_css_social",
 *   label = @Translation("Inline CSS - Open Social"),
 *   description = @Translation("Add inline CSS. Specific alter for Open Social to include the email template"),
 *   weight = 600,
 * )
 */
class InlineCssEmailAdjuster extends EmailAdjusterBase implements ContainerFactoryPluginInterface {

  /**
   * The asset resolver.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $assetResolver;

  /**
   * The CSS inliner.
   *
   * @var \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles
   */
  protected $cssInliner;

  /**
   * The CSS collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetOptimizerInterface
   */
  protected $cssOptimizer;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   The asset resolver.
   * @param \Drupal\Core\Asset\AssetOptimizerInterface $cssOptimizer
   *   The Drupal CSS optimizer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AssetResolverInterface $asset_resolver, AssetOptimizerInterface $cssOptimizer = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->assetResolver = $asset_resolver;
    $this->cssInliner = new CssToInlineStyles();
    $this->cssOptimizer = $cssOptimizer ?: \Drupal::service('asset.css.optimizer');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('asset.resolver'),
      $container->get('asset.css.optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postRender(EmailInterface $email): void {
    // Inline CSS.
    $assets = (new AttachedAssets())->setLibraries($email->getLibraries());
    $css = '';
    foreach ($this->assetResolver->getCssAssets($assets, FALSE) as $asset) {
      if (($asset['type'] == 'file') && $asset['preprocess']) {
        // Optimize to process @import.
        $css .= $this->cssOptimizer->optimize($asset);
      }
      else {
        $css .= file_get_contents($asset['data']);
      }
    }

    // Make sure to always run through the css Inline so our <style> tag that
    // lives within the email.html.template also gets converted.
    if (!empty($email->getHtmlBody())) {
      $email->setHtmlBody($this->cssInliner->convert($email->getHtmlBody(), $css));
    }
  }

}
