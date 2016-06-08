<?php

/**
 * @file
 * Token callbacks for the token module.
 */
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\system\Entity\Menu;
use Drupal\user\UserInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Implements hook_token_info_alter().
 */
function token_token_info_alter(&$info) {
  // Force 'date' type tokens to require input and add a 'current-date' type.
  // @todo Remove when http://drupal.org/node/943028 is fixed.
  $info['types']['date']['needs-data'] = 'date';
  $info['types']['current-date'] = array(
    'name' => t('Current date'),
    'description' => t('Tokens related to the current date and time.'),
    'type' => 'date',
  );

  // Add a 'dynamic' key to any tokens that have chained but dynamic tokens.
  $info['tokens']['date']['custom']['dynamic'] = TRUE;

  // The [file:size] may not always return in kilobytes.
  // @todo Remove when http://drupal.org/node/1193044 is fixed.
  if (!empty($info['tokens']['file']['size'])) {
    $info['tokens']['file']['size']['description'] = t('The size of the file.');
  }

  // Remove deprecated tokens from being listed.
  unset($info['tokens']['node']['tnid']);
  unset($info['tokens']['node']['type']);
  unset($info['tokens']['node']['type-name']);

  // Support 'url' type tokens for core tokens.
  if (isset($info['tokens']['comment']['url']) && \Drupal::moduleHandler()->moduleExists('comment')) {
    $info['tokens']['comment']['url']['type'] = 'url';
  }
  if (isset($info['tokens']['node']['url']) && \Drupal::moduleHandler()->moduleExists('node')) {
    $info['tokens']['node']['url']['type'] = 'url';
  }
  if (isset($info['tokens']['term']['url']) && \Drupal::moduleHandler()->moduleExists('taxonomy')) {
    $info['tokens']['term']['url']['type'] = 'url';
  }
  $info['tokens']['user']['url']['type'] = 'url';

  // Add [token:url] tokens for any URI-able entities.
  $entities = \Drupal::entityManager()->getDefinitions();
  foreach ($entities as $entity => $entity_info) {
    /* @var \Drupal\Core\Entity\EntityType $entity_info */
    if (!$entity_info->get('token_type')) {
      continue;
    }

    $token_type = $entity_info->get('token_type');
    if (!isset($info['types'][$token_type]) || !isset($info['tokens'][$token_type])) {
      continue;
    }

    // Add [entity:url] tokens if they do not already exist.
    // @todo Support entity:label
    if (!isset($info['tokens'][$token_type]['url'])) {
      $info['tokens'][$token_type]['url'] = array(
        'name' => t('URL'),
        'description' => t('The URL of the @entity.', array('@entity' => Unicode::strtolower($entity_info->getLabel()))),
        'module' => 'token',
        'type' => 'url',
      );
    }

    // Add [entity:original] tokens if they do not already exist.
    if (!isset($info['tokens'][$token_type]['original'])) {
      $info['tokens'][$token_type]['original'] = array(
        'name' => t('Original @entity', array('@entity' => Unicode::strtolower($entity_info->getLabel()))),
        'description' => t('The original @entity data if the @entity is being updated or saved.', array('@entity' => Unicode::strtolower($entity_info->getLabel()))),
        'module' => 'token',
        'type' => $token_type,
      );
    }
  }

  // Add support for custom date formats.
  // @todo Remove when http://drupal.org/node/1173706 is fixed.
  $date_format_types = \Drupal::entityManager()->getStorage('date_format')->loadMultiple();
  foreach ($date_format_types as $date_format_type => $date_format_type_info) {
    /* @var \Drupal\system\Entity\DateFormat $date_format_type_info */
    if (!isset($info['tokens']['date'][$date_format_type])) {
      $info['tokens']['date'][$date_format_type] = array(
        'name' => SafeMarkup::checkPlain($date_format_type_info->label()),
        'description' => t("A date in '@type' format. (%date)", array('@type' => $date_format_type, '%date' => format_date(REQUEST_TIME, $date_format_type))),
        'module' => 'token',
      );
    }
  }
}

/**
 * Implements hook_token_info().
 */
function token_token_info() {
  // Node tokens.
  $info['tokens']['node']['source'] = array(
    'name' => t('Translation source node'),
    'description' => t("The source node for this current node's translation set."),
    'type' => 'node',
  );
  $info['tokens']['node']['log'] = array(
    'name' => t('Revision log message'),
    'description' => t('The explanation of the most recent changes made to the node.'),
  );
  $info['tokens']['node']['content-type'] = array(
    'name' => t('Content type'),
    'description' => t('The content type of the node.'),
    'type' => 'content-type',
  );

  // Content type tokens.
  $info['types']['content-type'] = array(
    'name' => t('Content types'),
    'description' => t('Tokens related to content types.'),
    'needs-data' => 'node_type',
  );
  $info['tokens']['content-type']['name'] = array(
    'name' => t('Name'),
    'description' => t('The name of the content type.'),
  );
  $info['tokens']['content-type']['machine-name'] = array(
    'name' => t('Machine-readable name'),
    'description' => t('The unique machine-readable name of the content type.'),
  );
  $info['tokens']['content-type']['description'] = array(
    'name' => t('Description'),
    'description' => t('The optional description of the content type.'),
  );
  $info['tokens']['content-type']['node-count'] = array(
    'name' => t('Node count'),
    'description' => t('The number of nodes belonging to the content type.'),
  );
  $info['tokens']['content-type']['edit-url'] = array(
    'name' => t('Edit URL'),
    'description' => t("The URL of the content type's edit page."),
    // 'type' => 'url',
  );

  // Taxonomy term and vocabulary tokens.
  if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
    $info['tokens']['term']['edit-url'] = array(
      'name' => t('Edit URL'),
      'description' => t("The URL of the taxonomy term's edit page."),
      // 'type' => 'url',
    );
    $info['tokens']['term']['parents'] = array(
      'name' => t('Parents'),
      'description' => t("An array of all the term's parents, starting with the root."),
      'type' => 'array',
    );
    $info['tokens']['term']['root'] = array(
      'name' => t('Root term'),
      'description' => t("The root term of the taxonomy term."),
      'type' => 'term',
    );

    $info['tokens']['vocabulary']['machine-name'] = array(
      'name' => t('Machine-readable name'),
      'description' => t('The unique machine-readable name of the vocabulary.'),
    );
    $info['tokens']['vocabulary']['edit-url'] = array(
      'name' => t('Edit URL'),
      'description' => t("The URL of the vocabulary's edit page."),
      // 'type' => 'url',
    );
  }

  // File tokens.
  $info['tokens']['file']['basename'] = array(
    'name' => t('Base name'),
    'description' => t('The base name of the file.'),
  );
  $info['tokens']['file']['extension'] = array(
    'name' => t('Extension'),
    'description' => t('The extension of the file.'),
  );
  $info['tokens']['file']['size-raw'] = array(
    'name' => t('File byte size'),
    'description' => t('The size of the file, in bytes.'),
  );

  // User tokens.
  // Add information on the restricted user tokens.
  $info['tokens']['user']['cancel-url'] = array(
    'name' => t('Account cancellation URL'),
    'description' => t('The URL of the confirm delete page for the user account.'),
    'restricted' => TRUE,
    // 'type' => 'url',
  );
  $info['tokens']['user']['one-time-login-url'] = array(
    'name' => t('One-time login URL'),
    'description' => t('The URL of the one-time login page for the user account.'),
    'restricted' => TRUE,
    // 'type' => 'url',
  );
  $info['tokens']['user']['roles'] = array(
    'name' => t('Roles'),
    'description' => t('The user roles associated with the user account.'),
    'type' => 'array',
  );

  // Current user tokens.
  $info['tokens']['current-user']['ip-address'] = array(
    'name' => t('IP address'),
    'description' => 'The IP address of the current user.',
  );

  // Menu link tokens (work regardless if menu module is enabled or not).
  $info['types']['menu-link'] = array(
    'name' => t('Menu links'),
    'description' => t('Tokens related to menu links.'),
    'needs-data' => 'menu-link',
  );
  $info['tokens']['menu-link']['mlid'] = array(
    'name' => t('Link ID'),
    'description' => t('The unique ID of the menu link.'),
  );
  $info['tokens']['menu-link']['title'] = array(
    'name' => t('Title'),
    'description' => t('The title of the menu link.'),
  );
  $info['tokens']['menu-link']['url'] = array(
    'name' => t('URL'),
    'description' => t('The URL of the menu link.'),
    'type' => 'url',
  );
  $info['tokens']['menu-link']['parent'] = array(
    'name' => t('Parent'),
    'description' => t("The menu link's parent."),
    'type' => 'menu-link',
  );
  $info['tokens']['menu-link']['parents'] = array(
    'name' => t('Parents'),
    'description' => t("An array of all the menu link's parents, starting with the root."),
    'type' => 'array',
  );
  $info['tokens']['menu-link']['root'] = array(
    'name' => t('Root'),
    'description' => t("The menu link's root."),
    'type' => 'menu-link',
  );

  // Current page tokens.
  $info['types']['current-page'] = array(
    'name' => t('Current page'),
    'description' => t('Tokens related to the current page request.'),
  );
  $info['tokens']['current-page']['title'] = array(
    'name' => t('Title'),
    'description' => t('The title of the current page.'),
  );
  $info['tokens']['current-page']['url'] = array(
    'name' => t('URL'),
    'description' => t('The URL of the current page.'),
    'type' => 'url',
  );
  $info['tokens']['current-page']['page-number'] = array(
    'name' => t('Page number'),
    'description' => t('The page number of the current page when viewing paged lists.'),
  );
  $info['tokens']['current-page']['query'] = array(
    'name' => t('Query string value'),
    'description' => t('The value of a specific query string field of the current page.'),
    'dynamic' => TRUE,
  );

  // URL tokens.
  $info['types']['url'] = array(
    'name' => t('URL'),
    'description' => t('Tokens related to URLs.'),
    'needs-data' => 'path',
  );
  $info['tokens']['url']['path'] = array(
    'name' => t('Path'),
    'description' => t('The path component of the URL.'),
  );
  $info['tokens']['url']['relative'] = array(
    'name' => t('Relative URL'),
    'description' => t('The relative URL.'),
  );
  $info['tokens']['url']['absolute'] = array(
    'name' => t('Absolute URL'),
    'description' => t('The absolute URL.'),
  );
  $info['tokens']['url']['brief'] = array(
    'name' => t('Brief URL'),
    'description' => t('The URL without the protocol and trailing backslash.'),
  );
  $info['tokens']['url']['unaliased'] = array(
    'name' => t('Unaliased URL'),
    'description' => t('The unaliased URL.'),
    'type' => 'url',
  );
  $info['tokens']['url']['args'] = array(
    'name' => t('Arguments'),
    'description' => t("The specific argument of the current page (e.g. 'arg:1' on the page 'node/1' returns '1')."),
    'type' => 'array',
  );

  // Array tokens.
  $info['types']['array'] = array(
    'name' => t('Array'),
    'description' => t('Tokens related to arrays of strings.'),
    'needs-data' => 'array',
  );
  $info['tokens']['array']['first'] = array(
    'name' => t('First'),
    'description' => t('The first element of the array.'),
  );
  $info['tokens']['array']['last'] = array(
    'name' => t('Last'),
    'description' => t('The last element of the array.'),
  );
  $info['tokens']['array']['count'] = array(
    'name' => t('Count'),
    'description' => t('The number of elements in the array.'),
  );
  $info['tokens']['array']['reversed'] = array(
    'name' => t('Reversed'),
    'description' => t('The array reversed.'),
    'type' => 'array',
  );
  $info['tokens']['array']['keys'] = array(
    'name' => t('Keys'),
    'description' => t('The array of keys of the array.'),
    'type' => 'array',
  );
  $info['tokens']['array']['join'] = array(
    'name' => t('Imploded'),
    'description' => t('The values of the array joined together with a custom string in-between each value.'),
    'dynamic' => TRUE,
  );
  $info['tokens']['array']['value'] = array(
    'name' => t('Value'),
    'description' => t('The specific value of the array.'),
    'dynamic' => TRUE,
  );

  // Random tokens.
  $info['types']['random'] = array(
    'name' => t('Random'),
    'description' => ('Tokens related to random data.'),
  );
  $info['tokens']['random']['number'] = array(
    'name' => t('Number'),
    'description' => t('A random number from 0 to @max.', array('@max' => mt_getrandmax())),
  );
  $info['tokens']['random']['hash'] = array(
    'name' => t('Hash'),
    'description' => t('A random hash. The possible hashing algorithms are: @hash-algos.', array('@hash-algos' => implode(', ', hash_algos()))),
    'dynamic' => TRUE,
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function token_tokens($type, array $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  // Date tokens.
  if ($type == 'date') {
    $date = !empty($data['date']) ? $data['date'] : REQUEST_TIME;

    // @todo Remove when http://drupal.org/node/1173706 is fixed.
    $date_format_types = \Drupal::entityManager()->getStorage('date_format')->loadMultiple();
    foreach ($tokens as $name => $original) {
      if (isset($date_format_types[$name]) && _token_module('date', $name) == 'token') {
        $replacements[$original] = format_date($date, $name, '', NULL, $langcode);
      }
    }
  }

  // Current date tokens.
  // @todo Remove when http://drupal.org/node/943028 is fixed.
  if ($type == 'current-date') {
    $replacements += \Drupal::token()->generate('date', $tokens, array('date' => REQUEST_TIME), $options, $bubbleable_metadata);
  }

  // Comment tokens.
  if ($type == 'comment' && !empty($data['comment'])) {
    /* @var \Drupal\comment\CommentInterface $comment */
    $comment = $data['comment'];

    // Chained token relationships.
    if (($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url'))) {
      // Add fragment to url options.
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $comment->urlInfo('canonical', ['fragment' => "comment-{$comment->id()}"])), $options, $bubbleable_metadata);
    }
  }

  // Node tokens.
  if ($type == 'node' && !empty($data['node'])) {
    /* @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'log':
          $replacements[$original] = (string) $node->revision_log->value;
          break;
        case 'content-type':
          $type_name = \Drupal::entityManager()->getStorage('node_type')->load($node->getType())->label();
          $replacements[$original] = $type_name;
          break;
      }
    }

    // Chained token relationships.
    if (!empty($node->tnid) && ($parent_tokens = \Drupal::token()->findWithPrefix($tokens, 'source')) && $source_node = node_load($node->tnid)) {
      $replacements += \Drupal::token()->generate('node', $parent_tokens, array('node' => $source_node), $options, $bubbleable_metadata);
    }
    if (($node_type_tokens = \Drupal::token()->findWithPrefix($tokens, 'content-type')) && $node_type = node_type_load($node->bundle())) {
      $replacements += \Drupal::token()->generate('content-type', $node_type_tokens, array('node_type' => $node_type), $options, $bubbleable_metadata);
    }
    if (($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url'))) {
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $node->urlInfo()), $options, $bubbleable_metadata);
    }
  }

  // Content type tokens.
  if ($type == 'content-type' && !empty($data['node_type'])) {
    /* @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $data['node_type'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $replacements[$original] = $node_type->label();
          break;
        case 'machine-name':
          $replacements[$original] = $node_type->id();
          break;
        case 'description':
          $replacements[$original] = $node_type->getDescription();
          break;
        case 'node-count':
          $count = \Drupal::entityQueryAggregate('node')
            ->aggregate('nid', 'COUNT')
            ->condition('type', $node_type->id())
            ->execute();
          $replacements[$original] = (int) $count;
          break;
        case 'edit-url':
          $replacements[$original] = $node_type->url('edit-form', $url_options);
          break;
      }
    }
  }

  // Taxonomy term tokens.
  if ($type == 'term' && !empty($data['term'])) {
    /* @var \Drupal\taxonomy\TermInterface $term */
    $term = $data['term'];

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = \Drupal::entityManager()->getStorage('taxonomy_term');

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'edit-url':
          $replacements[$original] = \Drupal::url('entity.taxonomy_term.edit_form', ['taxonomy_term' => $term->id()], $url_options);
          break;

        case 'parents':
          if ($parents = token_taxonomy_term_load_all_parents($term->id())) {
            $replacements[$original] = token_render_array($parents, $options);
          }
          break;

        case 'root':
          $parents = $term_storage->loadAllParents($term->id());
          $root_term = end($parents);
          if ($root_term->id() != $term->id()) {
            $replacements[$original] = $root_term->label();
          }
          break;
      }
    }

    // Chained token relationships.
    if (($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url'))) {
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $term->urlInfo()), $options, $bubbleable_metadata);
    }
    // [term:parents:*] chained tokens.
    if ($parents_tokens = \Drupal::token()->findWithPrefix($tokens, 'parents')) {
      if ($parents = token_taxonomy_term_load_all_parents($term->id())) {
        $replacements += \Drupal::token()->generate('array', $parents_tokens, array('array' => $parents), $options, $bubbleable_metadata);
      }
    }
    if ($root_tokens = \Drupal::token()->findWithPrefix($tokens, 'root')) {
      $parents = $term_storage->loadAllParents($term->id());
      $root_term = end($parents);
      if ($root_term->tid != $term->id()) {
        $replacements += \Drupal::token()->generate('term', $root_tokens, array('term' => $root_term), $options, $bubbleable_metadata);
      }
    }
  }

  // Vocabulary tokens.
  if ($type == 'vocabulary' && !empty($data['vocabulary'])) {
    $vocabulary = $data['vocabulary'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'machine-name':
          $replacements[$original] = $vocabulary->id();
          break;
        case 'edit-url':
          $replacements[$original] = \Drupal::url('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()], $url_options);
          break;
      }
    }
  }

  // File tokens.
  if ($type == 'file' && !empty($data['file'])) {
    $file = $data['file'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'basename':
          $basename = pathinfo($file->uri->value, PATHINFO_BASENAME);
          $replacements[$original] = $basename;
          break;
        case 'extension':
          $extension = pathinfo($file->uri->value, PATHINFO_EXTENSION);
          $replacements[$original] = $extension;
          break;
        case 'size-raw':
          $replacements[$original] = (int) $file->filesize->value;
          break;
      }
    }
  }

  // User tokens.
  if ($type == 'user' && !empty($data['user'])) {
    /* @var \Drupal\user\UserInterface $account */
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'picture':
          if ($account instanceof UserInterface && $account->hasField('user_picture')) {
            /** @var \Drupal\Core\Render\RendererInterface $renderer */
            $renderer = \Drupal::service('renderer');
            $output = [
              '#theme' => 'user_picture',
              '#account' => $account,
            ];
            $replacements[$original] = $renderer->renderPlain($output);
          }
          break;

        case 'roles':
          $roles = $account->getRoles();
          $roles_names = array_combine($roles, $roles);
          $replacements[$original] = token_render_array($roles_names, $options);
          break;
      }
    }

    // Chained token relationships.
    if ($account instanceof UserInterface && $account->hasField('user_picture') && ($picture_tokens = \Drupal::token()->findWithPrefix($tokens, 'picture'))) {
      $replacements += \Drupal::token()->generate('file', $picture_tokens, array('file' => $account->user_picture->entity), $options, $bubbleable_metadata);
    }
    if ($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url')) {
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $account->urlInfo()), $options, $bubbleable_metadata);
    }
    if ($role_tokens = \Drupal::token()->findWithPrefix($tokens, 'roles')) {
      $roles = $account->getRoles();
      $roles_names = array_combine($roles, $roles);
      $replacements += \Drupal::token()->generate('array', $role_tokens, array('array' => $roles_names), $options, $bubbleable_metadata);
    }
  }

  // Current user tokens.
  if ($type == 'current-user') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'ip-address':
          $ip = \Drupal::request()->getClientIp();
          $replacements[$original] = $ip;
          break;
      }
    }
  }

  // Menu link tokens.
  if ($type == 'menu-link' && !empty($data['menu-link'])) {
    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $data['menu-link'];
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    if ($link instanceof MenuLinkContentInterface) {
      $link = $menu_link_manager->createInstance($link->getPluginId());
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $link->getPluginId();
          break;
        case 'title':
          $replacements[$original] = $link->getTitle();
          break;
        case 'url':
          $replacements[$original] = $link->getUrlObject()->setAbsolute()->toString();
          break;
        case 'parent':

          /** @var \Drupal\Core\Menu\MenuLinkInterface $parent */
          if ($link->getParent() && $parent = $menu_link_manager->createInstance($link->getParent())) {
            $replacements[$original] = $parent->getTitle();
          }
          break;
        case 'parents':
          if ($parents = token_menu_link_load_all_parents($link->getPluginId())) {
            $replacements[$original] = token_render_array($parents, $options);
          }
          break;
        case 'root';
          if ($link->getParent() && $parent_ids = array_keys(token_menu_link_load_all_parents($link->getPluginId()))) {
            $root = $menu_link_manager->createInstance(array_shift($parent_ids));
            $replacements[$original] = $root->getTitle();
          }
          break;
      }
    }

    // Chained token relationships.
    /** @var \Drupal\Core\Menu\MenuLinkInterface $parent */
    if ($link->getParent() && ($parent_tokens = \Drupal::token()->findWithPrefix($tokens, 'parent')) && $parent = $menu_link_manager->createInstance($link->getParent())) {
      $replacements += \Drupal::token()->generate('menu-link', $parent_tokens, array('menu-link' => $parent), $options, $bubbleable_metadata);
    }
    // [menu-link:parents:*] chained tokens.
    if ($parents_tokens = \Drupal::token()->findWithPrefix($tokens, 'parents')) {
      if ($parents = token_menu_link_load_all_parents($link->getPluginId())) {
        $replacements += \Drupal::token()->generate('array', $parents_tokens, array('array' => $parents), $options, $bubbleable_metadata);
      }
    }
    if (($root_tokens = \Drupal::token()->findWithPrefix($tokens, 'root')) && $link->getParent() && $parent_ids = array_keys(token_menu_link_load_all_parents($link->getPluginId()))) {
      $root = $menu_link_manager->createInstance(array_shift($parent_ids));
      $replacements += \Drupal::token()->generate('menu-link', $root_tokens, array('menu-link' => $root), $options, $bubbleable_metadata);
    }
    if ($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url')) {
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $link->getUrlObject()), $options, $bubbleable_metadata);
    }

  }

  // Current page tokens.
  if ($type == 'current-page') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          $request = \Drupal::request();
          $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
          $title = \Drupal::service('title_resolver')->getTitle($request, $route);
          $replacements[$original] = $title;
          break;
        case 'url':
          $replacements[$original] = Url::fromRoute('<current>', [], $url_options)->toString();
          break;
        case 'page-number':
          if ($page = \Drupal::request()->query->get('page')) {
            // @see PagerDefault::execute()
            $pager_page_array = explode(',', $page);
            $page = $pager_page_array[0];
          }
          $replacements[$original] = (int) $page + 1;
          break;
      }
    }

    // @deprecated
    // [current-page:arg] dynamic tokens.
    if ($arg_tokens = \Drupal::token()->findWithPrefix($tokens, 'arg')) {
      $path = ltrim(\Drupal::service('path.current')->getPath(), '/');
      // Make sure its a system path.
      $path = \Drupal::service('path.alias_manager')->getPathByAlias($path);
      foreach ($arg_tokens as $name => $original) {
        $parts = explode('/', $path);
        if (is_numeric($name) && isset($parts[$name])) {
          $replacements[$original] = $parts[$name];
        }
      }
    }

    // [current-page:query] dynamic tokens.
    if ($query_tokens = \Drupal::token()->findWithPrefix($tokens, 'query')) {
      foreach ($query_tokens as $name => $original) {
        if (\Drupal::request()->query->has($name)) {
          $value = \Drupal::request()->query->get($name);
          $replacements[$original] = $value;
        }
      }
    }

    // Chained token relationships.
    if ($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url')) {
      $url = Url::fromRoute('<current>');
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $url), $options, $bubbleable_metadata);
    }
  }

  // URL tokens.
  if ($type == 'url' && !empty($data['url'])) {
    /** @var \Drupal\Core\Url $url */
    $url = $data['url'];
    // To retrieve the correct path, modify a copy of the Url object.
    $path_url = clone $url;
    $path = '/' . $path_url->setAbsolute(FALSE)->setOption('fragment', NULL)->getInternalPath();

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'path':
          $value = !($url->getOption('alias')) ? \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode) : $path;
          $replacements[$original] = $value;
          break;
        case 'alias':
          // @deprecated
          $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode);
          $replacements[$original] = $alias;
          break;
        case 'absolute':
          $replacements[$original] = $url->setAbsolute()->toString();
          break;
        case 'relative':
          $replacements[$original] = $url->setAbsolute(FALSE)->toString();
          break;
        case 'brief':
          $replacements[$original] = preg_replace(array('!^https?://!', '!/$!'), '', $url->setAbsolute()->toString());
          break;
        case 'unaliased':
          $unaliased = clone $url;
          $replacements[$original] = $unaliased->setAbsolute()->setOption('alias', TRUE)->toString();
          break;
        case 'args':
          $value = !($url->getOption('alias')) ? \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode) : $path;
          $replacements[$original] = token_render_array(explode('/', $value), $options);
          break;

      }
    }

    // [url:args:*] chained tokens.
    if ($arg_tokens = \Drupal::token()->findWithPrefix($tokens, 'args')) {
      $value = !($url->getOption('alias')) ? \Drupal::service('path.alias_manager')->getAliasByPath($path, $langcode) : $path;
      $replacements += \Drupal::token()->generate('array', $arg_tokens, array('array' => explode('/', ltrim($value, '/'))), $options, $bubbleable_metadata);
    }

    // [url:unaliased:*] chained tokens.
    if ($unaliased_tokens = \Drupal::token()->findWithPrefix($tokens, 'unaliased')) {
      $url->setOption('alias', TRUE);
      $replacements += \Drupal::token()->generate('url', $unaliased_tokens, array('url' => $url), $options, $bubbleable_metadata);
    }
  }

  // Entity tokens.
  if (!empty($data[$type]) && $entity_type = \Drupal::service('token.entity_mapper')->getEntityTypeForTokenType($type)) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $data[$type];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'url':
          if (_token_module($type, 'url') == 'token' && $url = $entity->url()) {
            $replacements[$original] = $url;
          }
          break;

        case 'original':
          if (_token_module($type, 'original') == 'token' && !empty($entity->original)) {
            $label = $entity->original->label();
            $replacements[$original] = $label;
          }
          break;
      }
    }

    // [entity:url:*] chained tokens.
    if (($url_tokens = \Drupal::token()->findWithPrefix($tokens, 'url')) && _token_module($type, 'url') == 'token') {
      $replacements += \Drupal::token()->generate('url', $url_tokens, array('url' => $entity->urlInfo()), $options, $bubbleable_metadata);
    }

    // [entity:original:*] chained tokens.
    if (($original_tokens = \Drupal::token()->findWithPrefix($tokens, 'original')) && _token_module($type, 'original') == 'token' && !empty($entity->original)) {
      $replacements += \Drupal::token()->generate($type, $original_tokens, array($type => $entity->original), $options, $bubbleable_metadata);
    }

    // Pass through to an generic 'entity' token type generation.
    $entity_data = array(
      'entity_type' => $entity_type,
      'entity' => $entity,
      'token_type' => $type,
    );
    // @todo Investigate passing through more data like everything from entity_extract_ids().
    $replacements += \Drupal::token()->generate('entity', $tokens, $entity_data, $options, $bubbleable_metadata);
  }

  // Array tokens.
  if ($type == 'array' && !empty($data['array']) && is_array($data['array'])) {
    $array = $data['array'];

    $sort = isset($options['array sort']) ? $options['array sort'] : TRUE;
    $keys = token_element_children($array, $sort);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first':
          $value = $array[$keys[0]];
          $value = is_array($value) ? $renderer->renderPlain($value) : (string) $value;
          $replacements[$original] = $value;
          break;
        case 'last':
          $value = $array[$keys[count($keys) - 1]];
          $value = is_array($value) ? $renderer->renderPlain($value) : (string) $value;
          $replacements[$original] =$value;
          break;
        case 'count':
          $replacements[$original] = count($keys);
          break;
        case 'keys':
          $replacements[$original] = token_render_array($keys, $options);
          break;
        case 'reversed':
          $reversed = array_reverse($array, TRUE);
          $replacements[$original] = token_render_array($reversed, $options);
          break;
        case 'join':
          $replacements[$original] = token_render_array($array, array('join' => '') + $options);
          break;
      }
    }

    // [array:value:*] dynamic tokens.
    if ($value_tokens = \Drupal::token()->findWithPrefix($tokens, 'value')) {
      foreach ($value_tokens as $key => $original) {
        if ($key[0] !== '#' && isset($array[$key])) {
          $replacements[$original] = token_render_array_value($array[$key], $options);
        }
      }
    }

    // [array:join:*] dynamic tokens.
    if ($join_tokens = \Drupal::token()->findWithPrefix($tokens, 'join')) {
      foreach ($join_tokens as $join => $original) {
        $replacements[$original] = token_render_array($array, array('join' => $join) + $options);
      }
    }

    // [array:keys:*] chained tokens.
    if ($key_tokens = \Drupal::token()->findWithPrefix($tokens, 'keys')) {
      $replacements += \Drupal::token()->generate('array', $key_tokens, array('array' => $keys), $options, $bubbleable_metadata);
    }

    // [array:reversed:*] chained tokens.
    if ($reversed_tokens = \Drupal::token()->findWithPrefix($tokens, 'reversed')) {
      $replacements += \Drupal::token()->generate('array', $reversed_tokens, array('array' => array_reverse($array, TRUE)), array('array sort' => FALSE) + $options, $bubbleable_metadata);
    }

    // @todo Handle if the array values are not strings and could be chained.
  }

  // Random tokens.
  if ($type == 'random') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'number':
          $replacements[$original] = mt_rand();
          break;
      }
    }

    // [custom:hash:*] dynamic token.
    if ($hash_tokens = \Drupal::token()->findWithPrefix($tokens, 'hash')) {
      $algos = hash_algos();
      foreach ($hash_tokens as $name => $original) {
        if (in_array($name, $algos)) {
          $replacements[$original] = hash($name, Crypt::randomBytes(55));
        }
      }
    }
  }

  // If $type is a token type, $data[$type] is empty but $data[$entity_type] is
  // not, re-run token replacements.
  if (empty($data[$type]) && ($entity_type = \Drupal::service('token.entity_mapper')->getEntityTypeForTokenType($type)) && $entity_type != $type && !empty($data[$entity_type]) && empty($options['recursive'])) {
    $data[$type] = $data[$entity_type];
    $options['recursive'] = TRUE;
    $replacements += \Drupal::moduleHandler()->invokeAll('tokens', array($type, $tokens, $data, $options, $bubbleable_metadata));
  }

  // If the token type specifics a 'needs-data' value, and the value is not
  // present in $data, then throw an error.
  if (!empty($GLOBALS['drupal_test_info']['test_run_id'])) {
    // Only check when tests are running.
    $type_info = \Drupal::token()->getTypeInfo($type);
    if (!empty($type_info['needs-data']) && !isset($data[$type_info['needs-data']])) {
      trigger_error(t('Attempting to perform token replacement for token type %type without required data', array('%type' => $type)), E_USER_WARNING);
    }
  }

  return $replacements;
}

/**
 * Implements hook_token_info() on behalf of book.module.
 */
function book_token_info() {
  $info['tokens']['node']['book'] = array(
    'name' => t('Book'),
    'description' => t('The book page associated with the node.'),
    'type' => 'menu-link',
  );
  return $info;
}

/**
 * Implements hook_tokens() on behalf of book.module.
 */
function book_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  // Node tokens.
  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];

    if (!empty($node->book['mlid'])) {
      $link = token_book_link_load($node->book['mlid']);

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'book':
            $replacements[$original] = $link['title'];
            break;
        }
      }

      // Chained token relationships.
      if ($book_tokens = \Drupal::token()->findWithPrefix($tokens, 'book')) {
        $replacements += \Drupal::token()->generate('menu-link', $book_tokens, array('menu-link' => $link), $options, $bubbleable_metadata);
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_token_info() on behalf of menu_ui.module.
 */
function menu_ui_token_info() {
  // Menu tokens.
  $info['types']['menu'] = array(
    'name' => t('Menus'),
    'description' => t('Tokens related to menus.'),
    'needs-data' => 'menu',
  );
  $info['tokens']['menu']['name'] = array(
    'name' => t('Name'),
    'description' => t("The name of the menu."),
  );
  $info['tokens']['menu']['machine-name'] = array(
    'name' => t('Machine-readable name'),
    'description' => t("The unique machine-readable name of the menu."),
  );
  $info['tokens']['menu']['description'] = array(
    'name' => t('Description'),
    'description' => t('The optional description of the menu.'),
  );
  $info['tokens']['menu']['menu-link-count'] = array(
    'name' => t('Menu link count'),
    'description' => t('The number of menu links belonging to the menu.'),
  );
  $info['tokens']['menu']['edit-url'] = array(
    'name' => t('Edit URL'),
    'description' => t("The URL of the menu's edit page."),
  );

  $info['tokens']['menu-link']['menu'] = array(
    'name' => t('Menu'),
    'description' => t('The menu of the menu link.'),
    'type' => 'menu',
  );
  $info['tokens']['menu-link']['edit-url'] = array(
    'name' => t('Edit URL'),
    'description' => t("The URL of the menu link's edit page."),
  );
  $info['tokens']['node']['menu-link'] = array(
    'name' => t('Menu link'),
    'description' => t("The menu link for this node."),
    'type' => 'menu-link',
  );

  return $info;
}

/**
 * Implements hook_tokens() on behalf of menu_ui.module.
 */
function menu_ui_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  // Node tokens.
  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'menu-link':
          $url = $node->urlInfo();
          if ($links = $menu_link_manager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters())) {
            $link = reset($links);
            $replacements[$original] = $link->getTitle();
          }
          break;
      }

      // Chained token relationships.
      if ($menu_tokens = \Drupal::token()->findWithPrefix($tokens, 'menu-link')) {
        $url = $node->urlInfo();
        if ($links = $menu_link_manager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters())) {
          $link = reset($links);
          $replacements += \Drupal::token()->generate('menu-link', $menu_tokens, array('menu-link' => $link), $options, $bubbleable_metadata);
        }
      }
    }
  }

  // Menu link tokens.
  if ($type == 'menu-link' && !empty($data['menu-link'])) {
    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $data['menu-link'];

    if ($link instanceof MenuLinkContentInterface) {
      $link = $menu_link_manager->createInstance($link->getPluginId());
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'menu':
          if ($menu = Menu::load($link->getMenuName())) {
            $replacements[$original] = $menu->label();
          }
          break;

        case 'edit-url':
          $replacements[$original] = $link->getEditRoute()->setOptions($url_options)->toString();
          break;
      }
    }

    // Chained token relationships.
    if (($menu_tokens = \Drupal::token()->findWithPrefix($tokens, 'menu')) && $menu = Menu::load($link->getMenuName())) {
      $replacements += \Drupal::token()->generate('menu', $menu_tokens, array('menu' => $menu), $options, $bubbleable_metadata);
    }
  }

  // Menu tokens.
  if ($type == 'menu' && !empty($data['menu'])) {
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = $data['menu'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'name':
          $replacements[$original] = $menu->label();
          break;

        case 'machine-name':
          $replacements[$original] = $menu->id();
          break;

        case 'description':
          $replacements[$original] = $menu->getDescription();
          break;

        case 'menu-link-count':
          $replacements[$original] = $menu_link_manager->countMenuLinks($menu->id());
          break;

        case 'edit-url':
          $replacements[$original] = \Drupal::url('entity.menu.edit_form', ['menu' => $menu->id()], $url_options);
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Implements hook_token_info_alter() on behalf of field.module.
 *
 * We use hook_token_info_alter() rather than hook_token_info() as other
 * modules may already have defined some field tokens.
 */
function field_token_info_alter(&$info) {
  $type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();

  // Attach field tokens to their respecitve entity tokens.
  foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!$entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      continue;
    }

    // Make sure a token type exists for this entity.
    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type)) {
      continue;
    }

    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_name => $field) {
      if (!($field instanceof FieldStorageConfigInterface)) {
        continue;
      }
      // If a token already exists for this field, then don't add it.
      if (isset($info['tokens'][$token_type][$field_name])) {
        continue;
      }

      // Ensure the tokens exist.
      if (!isset($info['types'][$token_type]) || !isset($info['tokens'][$token_type])) {
        continue;
      }

      if ($token_type == 'comment' && $field_name == 'comment_body') {
        // Core provides the comment field as [comment:body].
        continue;
      }

      // Generate a description for the token.

      $labels = _token_field_label($entity_type_id, $field_name);
      $label = array_shift($labels);
      $params['@type'] = $type_info[$field->getType()]['label'];
      if (!empty($labels)) {
        $params['%labels'] = implode(', ', $labels);
        $description = t('@type field. Also known as %labels.', $params);
      }
      else {
        $description = t('@type field.', $params);
      }

      $info['tokens'][$token_type][$field_name] = array(
        'name' => SafeMarkup::checkPlain($label),
        'description' => $description,
        'module' => 'token',
      );
    }
  }
}

/**
 * Returns the label of a certain field.
 *
 * Therefore it looks up in all bundles to find the most used instance.
 *
 * Based on field_views_field_label().
 */
function _token_field_label($entity_type, $field_name) {
  $labels = [];
  // Count the amount of instances per label per field.
  foreach (array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type)) as $bundle) {
    $bundle_instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
    if (isset($bundle_instances[$field_name])) {
      $instance = $bundle_instances[$field_name];
      $label = $instance->getLabel();
      $labels[$label] = isset($labels[$label]) ? ++$labels[$label] : 1;
    }
  }

  if (empty($labels)) {
    return [$field_name];
  }

  // Sort the field labels by it most used label and return the labels.
  arsort($labels);
  return array_keys($labels);
}

/**
 * Implements hook_tokens() on behalf of field.module.
 */
function field_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  $langcode = isset($options['langcode']) ? $options['langcode'] : NULL;

  $renderer = \Drupal::service('renderer');

  // Entity tokens.
  if ($type == 'entity' && !empty($data['entity_type']) && !empty($data['entity']) && !empty($data['token_type'])) {
    // The field API does weird stuff to the entity, so let's clone it.
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = clone $data['entity'];
    if (!($entity instanceof ContentEntityInterface)) {
      return $replacements;
    }

    // Reset the prepared view flag in case token generation is called from
    // inside field_attach_view().
    unset($entity->_field_view_prepared);

    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')
      ->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.token');
    $token_view_display = (!empty($view_display) && $view_display->status());
    /** @var \Drupal\Core\Field\FieldTypePluginManager $fieldTypeManager */
    $fieldTypeManager = \Drupal::service('plugin.manager.field.field_type');

    foreach ($tokens as $name => $original) {
      // Replace the [entity:field-name] token only if token module added this
      // token.
      if ($entity->hasField($name) && _token_module($data['token_type'], $name) == 'token') {

        // Do not continue if the field is empty or not a configurable field.
        if ($entity->get($name)->isEmpty() || !($entity->getFieldDefinition($name) instanceof FieldConfigInterface)) {
          continue;
        }

        $display_options = 'token';
        if (!$token_view_display) {
          // We don't have the token view display and should fall back on
          // default formatters. If the field has specified a specific formatter
          // to be used by default with tokens, use that, otherwise use the
          // default formatter.
          $field_type_definition = $fieldTypeManager->getDefinition($entity->getFieldDefinition($name)->getType());

          $display_options = [
            'type' => !empty($field_type_definition['default_token_formatter']) ? $field_type_definition['default_token_formatter'] : $field_type_definition['default_formatter'],
            'label' => 'hidden',
          ];
        }

        $field_output = $entity->$name->view($display_options);
        $field_output['#token_options'] = $options;
        $field_output['#pre_render'][] = 'token_pre_render_field_token';
        $replacements[$original] = $renderer->renderPlain($field_output);
      }
    }

    // Remove the cloned object from memory.
    unset($entity);
  }

  return $replacements;
}

/**
 * Pre-render callback for field output used with tokens.
 */
function token_pre_render_field_token($elements) {
  // Remove the field theme hook, attachments, and JavaScript states.
  unset($elements['#theme']);
  unset($elements['#states']);
  unset($elements['#attached']);

  // Prevent multi-value fields from appearing smooshed together by appending
  // a join suffix to all but the last value.
  $deltas = Element::getVisibleChildren($elements);
  $count = count($deltas);
  if ($count > 1) {
    $join = isset($elements['#token_options']['join']) ? $elements['#token_options']['join'] : ", ";
    foreach ($deltas as $index => $delta) {
      // Do not add a suffix to the last item.
      if ($index < ($count - 1)) {
        $elements[$delta] += array('#suffix' => $join);
      }
    }
  }
  return $elements;
}
