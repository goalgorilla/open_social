<?php

/**
 * @file
 * Lists available colors and color schemes for the Social Blue theme.
 */

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'brand-bg-primary' => t('Primary background color'),
    'brand-bg-secondary' => t('Secondary background color'),
    'brand-bg-accent' => t('Accent backround color'),
    'brand-text-primary' => t('Primary text color'),
    'brand-text-secondary' => t('Secondary text color'),
    'brand-text-accent' => t('Accent text color'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Social Blue (default)'),
      'colors' => array(
        'brand-bg-primary' => '#29abe2',
        'brand-bg-secondary' => '#1f80aa',
        'brand-bg-accent' => '#ffc142',
        'brand-text-primary' => '#29abe2',
        'brand-text-secondary' => '#31708f',
        'brand-text-accent' => '#ffc142',
      ),
    ),
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  // @TODO make sure we rewrite the correct color.css
  'css' => array(
    'assets/css/brand.css',
  ),

  // Files to copy.
  //  'copy' => array(
  //    'logo.svg',
  //  ),

  // Gradient definitions.
  //  'gradients' => array(
  //    array(
  //      // (x, y, width, height).
  //      'dimension' => array(0, 0, 0, 0),
  //      // Direction of gradient ('vertical' or 'horizontal').
  //      'direction' => 'vertical',
  //      // Keys of colors to use for the gradient.
  //      'colors' => array('top', 'bottom'),
  //    ),
  //  ),

  // Preview files.
  // @todo Make sure we update the preview it will help
  // Distro users / SaaS users a lot!
  // 'preview_library' => 'socialbase/color.preview',
   'preview_html' => '',

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
