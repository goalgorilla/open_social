<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Utility;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

/**
 * Strips HTML tags from fulltext fields and decodes HTML entities.
 *
 * @SearchApiProcessor(
 *   id = "html_filter",
 *   label = @Translation("HTML filter"),
 *   description = @Translation("Strips HTML tags from fulltext fields and decodes HTML entities. Use this processor when indexing HTML data, e.g., node bodies for certain text formats. The processor also allows to boost (or ignore) the contents of specific elements."),
 *   stages = {
 *     "preprocess_index" = -10,
 *     "preprocess_query" = -10
 *   }
 * )
 */
class HtmlFilter extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'title' => TRUE,
      'alt' => TRUE,
      'tags' => array(
        'h1' => 5,
        'h2' => 3,
        'h3' => 2,
        'strong' => 2,
        'b' => 2,
        'em' => 1.5,
        'u' => 1.5,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['title'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Index title attribute'),
      '#description' => $this->t('If set, the contents of title attributes will be indexed.'),
      '#default_value' => $this->configuration['title'],
    );

    $form['alt'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Index alt attribute'),
      '#description' => $this->t('If set, the alternative text of images will be indexed.'),
      '#default_value' => $this->configuration['alt'],
    );

    $dumper = new Dumper();
    $tags = $dumper->dump($this->configuration['tags'], 2);
    $tags = str_replace('\r\n', "\n", $tags);
    $tags = str_replace('"', '', $tags);

    $t_args[':url'] = Url::fromUri('https://en.wikipedia.org/wiki/YAML')->toString();
    $form['tags'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Tag boosts'),
      '#description' => $this->t('Specify special boost values for certain HTML elements, in <a href=":url">YAML file format</a>. The boost values of nested elements are multiplied, elements not mentioned will have the default boost value of 1. Assign a boost of 0 to ignore the text content of that HTML element.', $t_args),
      '#default_value' => $tags,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $tags = trim($form_state->getValue('tags'));
    if (!$tags) {
      $form_state->setValue('tags', array());
      return;
    }
    $errors = array();
    try {
      $parser = new Parser();
      $tags = $parser->parse($tags);
      if (!is_array($tags)) {
        $errors[] = $this->t('Tags is not a valid YAML map. See @link for information on how to write correctly formed YAML.', array('@link' => 'http://yaml.org'));
        $tags = array();
      }
    }
    catch (ParseException $exception) {
      $errors[] = $this->t('Tags is not valid YAML. See @link for information on how to write correctly formed YAML.', array('@link' => 'http://yaml.org'));
      $tags = array();
    }
    foreach ($tags as $key => $value) {
      $tag = "<$key>";
      if (is_array($value)) {
        $errors[] = $this->t("Boost value for tag @tag can't be an array.", array('@tag' => $tag));
      }
      elseif (!is_numeric($value)) {
        $errors[] = $this->t("Boost value for tag @tag must be numeric.", array('@tag' => $tag));
      }
      elseif ($value < 0) {
        $errors[] = $this->t('Boost value for tag @tag must be non-negative.', array('@tag' => $tag));
      }
      elseif ($value == 1) {
        unset($tags[$key]);
      }
      else {
        $tags[$key] = (float) $value;
      }
    }
    $form_state->setValue('tags', $tags);
    if ($errors) {
      $form_state->setError($form['tags'], implode("<br />\n", $errors));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function processFieldValue(&$value, &$type) {
    // Remove invisible content.
    $text = preg_replace('@<(applet|audio|canvas|command|embed|iframe|map|menu|noembed|noframes|noscript|script|style|svg|video)[^>]*>.*</\1>@siU', ' ', $value);
    // Let removed tags still delimit words.
    $is_text_type = Utility::isTextType($type, array('text', 'tokenized_text'));
    if ($is_text_type) {
      $text = str_replace(array('<', '>'), array(' <', '> '), $text);
      if ($this->configuration['title']) {
        $text = preg_replace('/(<[-a-z_]+[^>]*["\s])title\s*=\s*("([^"]+)"|\'([^\']+)\')([^>]*>)/i', '$1 $5 $3$4 ', $text);
      }
      if ($this->configuration['alt']) {
        $text = preg_replace('/<[-a-z_]+[^>]*["\s]alt\s*=\s*("([^"]+)"|\'([^\']+)\')[^>]*>/i', ' <img>$2$3</img> ', $text);
      }
    }
    if ($this->configuration['tags'] && $is_text_type) {
      $text = strip_tags($text, '<' . implode('><', array_keys($this->configuration['tags'])) . '>');
      $value = $this->parseHtml($text);
      $type = 'tokenized_text';
    }
    else {
      $text = strip_tags($text);
      $value = $this->normalizeText(trim($text));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    $value = str_replace(array('<', '>'), array(' <', '> '), $value);
    $value = strip_tags($value);
    $value = $this->normalizeText($value);
  }

  /**
   * Tokenizes an HTML string according to the HTML elements.
   *
   * Assigns boost values to the elements' contents accordingly.
   *
   * @param string $text
   *   The HTML string to parse, passed by reference. After the method call, the
   *   variable will contain the portion of the string after the current
   *   element, or an empty string (if there is no current element).
   * @param string|null $active_tag
   *   (optional) The currently active tag, for which a closing tag has to be
   *   found. Internal use only.
   * @param float $boost
   *   (optional) The currently active boost value. Internal use only.
   *
   * @return array
   *   Tokenized text with appropriate scores.
   */
  protected function parseHtml(&$text, $active_tag = NULL, $boost = 1.0) {
    $ret = array();
    while (($pos = strpos($text, '<')) !== FALSE) {
      if ($boost && $pos > 0) {
        $value = $this->normalizeText(substr($text, 0, $pos));
        if ($value !== '') {
          $ret[] = Utility::createTextToken($value, $boost);
        }
      }
      $text = substr($text, $pos + 1);
      preg_match('#^(/?)([-:_a-zA-Z0-9]+)#', $text, $m);
      $pos = strpos($text, '>');
      $empty_tag = $text[$pos - 1] == '/';
      $text = substr($text, $pos + 1);
      if ($m[1]) {
        // Closing tag.
        if ($active_tag && $m[2] == $active_tag) {
          return $ret;
        }
      }
      elseif (!$empty_tag) {
        // Opening tag => recursive call.
        $inner_boost = $boost * (isset($this->configuration['tags'][$m[2]]) ? $this->configuration['tags'][$m[2]] : 1);
        $ret = array_merge($ret, $this->parseHtml($text, $m[2], $inner_boost));
      }
    }
    if ($text) {
      $value = $this->normalizeText($text);
      if ($value !== '') {
        $ret[] = Utility::createTextToken($value, $boost);
      }
      $text = '';
    }
    return $ret;
  }

  /**
   * Removes superfluous whitespace and unescapes HTML entities.
   *
   * @param string $value
   *   The text to process.
   *
   * @return string
   *   The text without unnecessary whitespace and HTML entities transformed
   *   back to plain text.
   */
  protected function normalizeText($value) {
    $value = Html::decodeEntities($value);
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    return $value;
  }

}
