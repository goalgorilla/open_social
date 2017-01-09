<?php

/**
 * @file
 * Contains Drupal\mentions\Plugin\Filter\MentionsFilter.
 */

namespace Drupal\mentions\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\mentions\MentionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\mentions\MentionsPluginManager;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;

/**
 * Class FilterMentions.
 *
 * @package Drupal\mentions\Plugin\Filter
 *
 * @Filter(
 * id = "filter_mentions",
 * title = @Translation("Mentions Filter"),
 * description = @Translation("Configure via the <a href='/admin/structure/mentions'>Mention types</a> page."),
 * type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 * settings = {
 *   "mentions_filter" = {}
 * },
 * weight = -10
 * )
 */
class MentionsFilter extends FilterBase implements ContainerFactoryPluginInterface {

  protected $entityManager;
  protected $renderer;
  protected $config;
  protected $mentionsManager;

  private $tokenService;
  private $mentionTypes = [];
  private $entityQueryService;
  private $inputSettings = [];
  private $outputSettings = [];
  private $textFormat;

  /**
   * MentionsFilter constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   * @param \Drupal\Core\Render\RendererInterface $render
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\mentions\MentionsPluginManager $mentions_manager
   * @param \Drupal\Core\Utility\Token $token
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_manager, RendererInterface $render, ConfigFactory $config, MentionsPluginManager $mentions_manager, Token $token, QueryFactory $query_factory) {
    $this->entityManager = $entity_manager;
    $this->mentionsManager = $mentions_manager;
    $this->renderer = $render;
    $this->config = $config;
    $this->tokenService = $token;
    $this->entityQueryService = $query_factory;

    if (!isset($plugin_definition['provider'])) {
      $plugin_definition['provider'] = 'mentions';
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    $renderer = $container->get('renderer');
    $config = $container->get('config.factory');
    $mentions_manager = $container->get('plugin.manager.mentions');
    $token = $container->get('token');
    $entity_service = $container->get('entity.query');

    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $entity_manager,
      $renderer,
      $config,
      $mentions_manager,
      $token,
      $entity_service
    );
  }

  public function getSettings() {
    return $this->settings;
  }

  public function checkMentionTypes() {
    $settings = $this->settings;

    if (isset($settings['mentions_filter'])) {
      $configs = $this->config->listAll('mentions.mentions_type');

      foreach ($configs as $config) {
        $this->mentionTypes[] = str_replace('mentions.mentions_type.', '', $config);
      }
    }

    return !empty($this->mentionTypes);
  }

  /**
   * @return bool
   */
  public function shouldApplyFilter() {
    if ($this->checkMentionTypes()) {
      return TRUE;
    }
    elseif ($this->textFormat && ($format = FilterFormat::load($this->textFormat))) {
      $filters = $format->get('filters');

      if (!empty($filters['filter_mentions']['status'])) {
        $this->settings = $filters['filter_mentions']['settings'];

        return $this->checkMentionTypes();
      }
    }

    return FALSE;
  }

  /**
   * @param $text string
   * @return array
   */
  public function getMentions($text) {
    $mentions = [];
    $config_names = $this->mentionTypes;

    foreach ($config_names as $config_name) {
      $settings = $this->config->get('mentions.mentions_type.' . $config_name);
      $input_settings = [
        'prefix' => $settings->get('input.prefix'),
        'suffix' => $settings->get('input.suffix'),
        'entity_type' => $settings->get('input.entity_type'),
        'value' => $settings->get('input.inputvalue'),
      ];
      $this->inputSettings[$config_name] = $input_settings;

      if (!isset($input_settings['entity_type']) || empty($this->settings['mentions_filter'][$config_name])) {
        continue;
      }

      $output_settings = [
        'value' => $settings->get('output.outputvalue'),
        'renderlink' => (bool) $settings->get('output.renderlink'),
        'rendertextbox' => $settings->get('output.renderlinktextbox'),
      ];
      $this->outputSettings[$config_name] = $output_settings;
      $mention_type = $settings->get('mention_type');
      $mention = $this->mentionsManager->createInstance($mention_type);

      if ($mention instanceof MentionsPluginInterface) {
        $pattern = '/(?:' . preg_quote($input_settings['prefix']) . ')([a-zA-Z0-9_]+)' . preg_quote($input_settings['suffix']) . '/';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
          $target = $mention->targetCallback($match[1], $input_settings);

          if ($target !== FALSE) {
            $mentions[$match[0]] = array(
              'type' => $mention_type,
              'source' => array(
                'string' => $match[0],
                'match' => $match[1],
              ),
              'target' => $target,
              'config_name' => $config_name,
            );
          }
        }
      }
    }

    return $mentions;
  }

  /**
   * @param $text string
   * @return string
   */
  public function filterMentions($text) {
    $mentions = $this->getMentions($text);

    foreach ($mentions as $match) {
      $mention = $this->mentionsManager->createInstance($match['type']);

      if ($mention instanceof MentionsPluginInterface) {
        $output_settings = $this->outputSettings[$match['config_name']];
        $output = $mention->outputCallback($match, $output_settings);
        $build = array(
          '#theme' => 'mention_link',
          '#mention_id' => $match['target']['entity_id'],
          '#link' => $output['link'],
          '#render_link' => $output_settings['renderlink'],
          '#render_value' => $output['value'],
        );
        $mentions = $this->renderer->render($build);
        $text = str_replace($match['source']['string'], $mentions, $text);
      }
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($this->shouldApplyFilter()) {
      $text = $this->filterMentions($text);

      return new FilterProcessResult($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $configs = $this->config->listAll('mentions.mentions_type');
    $candidate_entitytypes = array();

    foreach ($configs as $config) {
      $mentions_name = str_replace('mentions.mentions_type.', '', $config);
      $candidate_entitytypes[$mentions_name] = $mentions_name;
    }

    if (count($candidate_entitytypes) == 0) {
      return NULL;
    }

    $form['mentions_filter'] = array(
      '#type' => 'checkboxes',
      '#options' => $candidate_entitytypes,
      '#default_value' => $this->settings['mentions_filter'],
      '#title' => $this->t('Mentions types'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setTextFormat($text_format) {
    $this->textFormat = $text_format;
  }

}
