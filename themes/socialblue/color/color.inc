<?php

/**
 * @file
 * Lists available colors and color schemes for the Social Blue theme.
 */

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'bg-primary' => t('Primary color'),
    'brand-secondary' => t('Secondary color'),
    'brand-accent' => t('Accent color'),
    'text-primary' => t('Text primary color'),
    'text-info' => t('Text secondary color'),
    'text-accent' => t('Text accent color'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Social Blue (default)'),
      'colors' => array(
        'bg-primary' => '#29abe2',
        'brand-secondary' => '#1f80aa',
        'brand-accent' => '#ffc142',
        'text-primary' => '#29abe2',
        'text-info' => '#31708f',
        'text-accent' => '#ffc142',
      ),
    ),
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  // @TODO make sure we rewrite the correct color.css
  'css' => array(
    'assets/css/base.css',
  ),

  // Files to copy.
  //  'copy' => array(
  //    'logo.svg',
  //  ),

  // Gradient definitions.
  'gradients' => array(
    array(
      // (x, y, width, height).
      'dimension' => array(0, 0, 0, 0),
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      'colors' => array('top', 'bottom'),
    ),
  ),

  // Preview files. @todo Make sure we update the preview it will help
  // Distro users / SaaS users a lot!
  // 'preview_library' => 'socialbase/color.preview',
  // 'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'socialblue'),
      ],
    ],
  ],
);
