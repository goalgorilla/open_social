<?php

namespace Drupal\social_demo;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

/**
 * Class DemoContent.
 *
 * @package Drupal\social_demo
 */
abstract class DemoContent extends PluginBase implements DemoContentInterface {

  /**
   * Contains the created content.
   *
   * @var array
   */
  protected $content = [];

  /**
   * Contains data from a file.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Parser.
   *
   * @var \Drupal\social_demo\DemoContentParserInterface
   */
  protected $parser;

  /**
   * Profile.
   *
   * @var string
   */
  protected $profile = '';

  /**
   * Contains the entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    $definition = $this->getPluginDefinition();
    return isset($definition['source']) ? $definition['source'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setProfile($profile) {
    $this->profile = $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    $definition = $this->getPluginDefinition();
    return isset($definition['provider']) ? $definition['provider'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    return isset($this->profile) ? $this->profile : '';
  }

  /**
   * {@inheritdoc}
   */
  public function removeContent() {
    $data = $this->fetchData();

    foreach ($data as $uuid => $item) {
      // Must have uuid and same key value.
      if ($uuid !== $item['uuid']) {
        continue;
      }

      $entities = $this->entityStorage->loadByProperties([
        'uuid' => $uuid,
      ]);

      foreach ($entities as $entity) {
        $entity->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->content);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityStorage(EntityStorageInterface $entity_storage) {
    $this->entityStorage = $entity_storage;
  }

  /**
   * Gets the data from a file.
   */
  protected function fetchData() {
    if (!$this->data) {
      $this->data = $this->parser->parseFileFromModule($this->getSource(), $this->getModule(), $this->getProfile());
    }

    return $this->data;
  }

  /**
   * Load entity by uuid.
   *
   * @param string $entity_type_id
   *   Identifier of entity type.
   * @param string|int $id
   *   Identifier or uuid.
   * @param bool $all
   *   If set true, method will return all loaded entity.
   *   If set false, will return only one.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\EntityInterface[]|mixed
   *   Returns the entity.
   */
  protected function loadByUuid($entity_type_id, $id, $all = FALSE) {
    if (property_exists($this, $entity_type_id . 'Storage')) {
      $storage = $this->{$entity_type_id . 'Storage'};
    }
    else {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    }

    if (is_numeric($id)) {
      $entities = $storage->loadByProperties([
        'uid' => $id,
      ]);
    }
    else {
      $entities = $storage->loadByProperties([
        'uuid' => $id,
      ]);
    }

    if (!$all) {
      return current($entities);
    }

    return $entities;
  }

  /**
   * Extract the mention from the content by [~Uuid].
   *
   * @param string $content
   *   The content that contains the mention.
   *
   * @return mixed
   *   If nothing needs to be replaced, just return the same content.
   */
  protected function checkMentionOrLinkByUuid($content) {
    // Check if there's a mention in the given content.
    if (strpos($content, '[~') !== FALSE || strpos($content, '[link=') !== FALSE) {
      // Put the content in a logical var.
      $input = $content;
      $mention_uuid = '';
      $link_uuid = '';

      // Uuid validation check.
      $isValidUuid = '/^[0-9A-F]{8}-[0-9A-F]{4}-[1-5][0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

      if (strpos($content, '[~') !== FALSE) {
        // Strip the mention uuid from the content.
        preg_match('/~(.*?)]/', $input, $output);
        $mention_uuid = $output[1];
        // If the uuid is not according the uuid v1 or v4 format
        // then just return the content.
        if (!preg_match($isValidUuid, $mention_uuid)) {
          return $content;
        }
      }
      if (strpos($content, '[link=') !== FALSE) {
        // Strip the link uuid from the content.
        preg_match('/=(.*?)]/', $input, $output);
        $link_uuid = $output[1];
        // If the uuid is not according the uuid v1 or v4 format
        // then just return the content.
        if (!preg_match($isValidUuid, $link_uuid)) {
          return $content;
        }
      }

      if (!empty($mention_uuid) || !empty($link_uuid)) {
        // Load the account by uuid.
        $account = $this->loadByUuid('user', $mention_uuid);
        if ($account instanceof User) {
          // Load the profile by account id.
          $profile = $this->loadByUuid('profile', $account->id());
          if ($profile instanceof Profile) {
            $mention = preg_replace('/' . $mention_uuid . '/', $profile->id(), $content);
            $content = $mention;
          }
        }
        // Load the node by uuid.
        $node = $this->loadByUuid('node', $link_uuid);
        if ($node instanceof Node) {
          $options = ['absolute' => TRUE];
          $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], $options)->toString();
          // Prepare the link.
          $link = '<a href="' . $url . '">' . $node->getTitle() . '</a>';
          // Replace the uuid with the link.
          $link_replacement = preg_replace('/\[link=' . $link_uuid . ']/', $link, $content);
          $content = $link_replacement;
        }
      }

      // Return the content with the replaced mention and/or link.
      return $content;
    }

    // Return the content as it was given.
    return $content;
  }

  /**
   * Prepares data about an image.
   *
   * @param string $picture
   *   The image uuid.
   * @param string $alt
   *   The image alt text.
   *
   * @return array
   *   Returns an array for the image field.
   */
  protected function prepareImage($picture, $alt = '') {
    $value = NULL;
    $files = $this->loadByUuid('file', $picture);

    if ($files instanceof File) {
      $value = [
        [
          'target_id' => $files->id(),
          'alt' => $alt ?: 'file' . $files->id(),
        ],
      ];
    }

    return $value;
  }

  /**
   * Makes an array with data of an entity.
   *
   * @param array $item
   *   Array with items.
   *
   * @return array
   *   Returns an array.
   */
  abstract protected function getEntry(array $item);

  /**
   * Scramble it.
   *
   * @param array $data
   */
  public function scrambleData(array $data, $max = NULL) {
    $new_data = [];
    for ($i=0; $i < $max; $i++) {
      // Get a random item from the array.
      $old_uuid = array_rand($data);
      $item = $data[$old_uuid];
      $uuid = 'ScrambledDemo_' . time() . '_' . $i;
      $item['uuid'] = $uuid;
      $new_data[$uuid] = $item;
    }
    return $new_data;
  }

  public static function ipsum($nparagraphs) {
    $paragraphs = [];
    for($p=0; $p<$nparagraphs; ++$p) {
      $nsentences = random_int(3,8);
      $sentences = [];
      for($s=0; $s<$nsentences; ++$s) {
        $frags = [];
        $commaChance = .33;
        while(true) {
          $nwords = random_int(3, 15);
          $words = self::random_values(self::$lorem, $nwords);
          $frags[] = implode(' ', $words);
          if(self::random_float() >= $commaChance) {
            break;
          }
          $commaChance /= 2;
        }

        $sentences[] = ucfirst(implode(', ', $frags)) . '.';
      }
      $paragraphs[] = implode(' ',$sentences);
    }
    return implode("\n\n",$paragraphs);
  }

  private static function random_float() {
    return random_int(0, PHP_INT_MAX-1)/PHP_INT_MAX;
  }

  private static function random_values($arr, $count) {
    $keys = array_rand($arr, $count);
    if($count == 1) {
      $keys = [$keys];
    }
    return array_intersect_key($arr, array_fill_keys($keys, null));
  }

  private static $lorem = [
    0 => 'lorem',
    1 => 'ipsum',
    2 => 'dolor',
    3 => 'sit',
    4 => 'amet',
    5 => 'consectetur',
    6 => 'adipiscing',
    7 => 'elit',
    8 => 'praesent',
    9 => 'interdum',
    10 => 'dictum',
    11 => 'mi',
    12 => 'non',
    13 => 'egestas',
    14 => 'nulla',
    15 => 'in',
    16 => 'lacus',
    17 => 'sed',
    18 => 'sapien',
    19 => 'placerat',
    20 => 'malesuada',
    21 => 'at',
    22 => 'erat',
    23 => 'etiam',
    24 => 'id',
    25 => 'velit',
    26 => 'finibus',
    27 => 'viverra',
    28 => 'maecenas',
    29 => 'mattis',
    30 => 'volutpat',
    31 => 'justo',
    32 => 'vitae',
    33 => 'vestibulum',
    34 => 'metus',
    35 => 'lobortis',
    36 => 'mauris',
    37 => 'luctus',
    38 => 'leo',
    39 => 'feugiat',
    40 => 'nibh',
    41 => 'tincidunt',
    42 => 'a',
    43 => 'integer',
    44 => 'facilisis',
    45 => 'lacinia',
    46 => 'ligula',
    47 => 'ac',
    48 => 'suspendisse',
    49 => 'eleifend',
    50 => 'nunc',
    51 => 'nec',
    52 => 'pulvinar',
    53 => 'quisque',
    54 => 'ut',
    55 => 'semper',
    56 => 'auctor',
    57 => 'tortor',
    58 => 'mollis',
    59 => 'est',
    60 => 'tempor',
    61 => 'scelerisque',
    62 => 'venenatis',
    63 => 'quis',
    64 => 'ultrices',
    65 => 'tellus',
    66 => 'nisi',
    67 => 'phasellus',
    68 => 'aliquam',
    69 => 'molestie',
    70 => 'purus',
    71 => 'convallis',
    72 => 'cursus',
    73 => 'ex',
    74 => 'massa',
    75 => 'fusce',
    76 => 'felis',
    77 => 'fringilla',
    78 => 'faucibus',
    79 => 'varius',
    80 => 'ante',
    81 => 'primis',
    82 => 'orci',
    83 => 'et',
    84 => 'posuere',
    85 => 'cubilia',
    86 => 'curae',
    87 => 'proin',
    88 => 'ultricies',
    89 => 'hendrerit',
    90 => 'ornare',
    91 => 'augue',
    92 => 'pharetra',
    93 => 'dapibus',
    94 => 'nullam',
    95 => 'sollicitudin',
    96 => 'euismod',
    97 => 'eget',
    98 => 'pretium',
    99 => 'vulputate',
    100 => 'urna',
    101 => 'arcu',
    102 => 'porttitor',
    103 => 'quam',
    104 => 'condimentum',
    105 => 'consequat',
    106 => 'tempus',
    107 => 'hac',
    108 => 'habitasse',
    109 => 'platea',
    110 => 'dictumst',
    111 => 'sagittis',
    112 => 'gravida',
    113 => 'eu',
    114 => 'commodo',
    115 => 'dui',
    116 => 'lectus',
    117 => 'vivamus',
    118 => 'libero',
    119 => 'vel',
    120 => 'maximus',
    121 => 'pellentesque',
    122 => 'efficitur',
    123 => 'class',
    124 => 'aptent',
    125 => 'taciti',
    126 => 'sociosqu',
    127 => 'ad',
    128 => 'litora',
    129 => 'torquent',
    130 => 'per',
    131 => 'conubia',
    132 => 'nostra',
    133 => 'inceptos',
    134 => 'himenaeos',
    135 => 'fermentum',
    136 => 'turpis',
    137 => 'donec',
    138 => 'magna',
    139 => 'porta',
    140 => 'enim',
    141 => 'curabitur',
    142 => 'odio',
    143 => 'rhoncus',
    144 => 'blandit',
    145 => 'potenti',
    146 => 'sodales',
    147 => 'accumsan',
    148 => 'congue',
    149 => 'neque',
    150 => 'duis',
    151 => 'bibendum',
    152 => 'laoreet',
    153 => 'elementum',
    154 => 'suscipit',
    155 => 'diam',
    156 => 'vehicula',
    157 => 'eros',
    158 => 'nam',
    159 => 'imperdiet',
    160 => 'sem',
    161 => 'ullamcorper',
    162 => 'dignissim',
    163 => 'risus',
    164 => 'aliquet',
    165 => 'habitant',
    166 => 'morbi',
    167 => 'tristique',
    168 => 'senectus',
    169 => 'netus',
    170 => 'fames',
    171 => 'nisl',
    172 => 'iaculis',
    173 => 'cras',
    174 => 'aenean',
  ];

  public function getRandomUserId() {

  }

}
