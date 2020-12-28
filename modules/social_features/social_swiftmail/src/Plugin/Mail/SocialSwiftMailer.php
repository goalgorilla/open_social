<?php

namespace Drupal\social_swiftmail\Plugin\Mail;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\mailsystem\MailsystemManager;
use Drupal\swiftmailer\Plugin\Mail\SwiftMailer;
use Html2Text\Html2Text;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Provides a 'Forced HTML SwiftMailer' plugin to send emails.
 *
 * @Mail(
 *   id = "social_swiftmailer",
 *   label = @Translation("Social Swift Mailer"),
 *   description = @Translation("Forces the given body text to be HTML.")
 * )
 */
class SocialSwiftMailer extends SwiftMailer {

  /**
   * {@inheritdoc}
   */
  protected function massageMessageBody(array &$message, $is_html) {
    $text_format = $message['params']['text_format'] ?? $this->config['message']['text_format'] ?: NULL;
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    $body = [];

    foreach ($message['body'] as $part) {
      if (!($part instanceof MarkupInterface)) {
        if ($is_html) {
          // Convert to HTML. The default 'plain_text' format escapes markup,
          // converts new lines to <br> and converts URLs to links.
          $body[] = check_markup($part, $text_format);
        }
        else {
          // The body will be plain text. However we need to convert to HTML
          // to render the template then convert back again. Use a fixed
          // conversion because we don't want to convert URLs to links.
          $body[] = preg_replace("|\n|", "<br />\n", HTML::escape($part)) . "<br />\n";
        }
      }
      else {
        $body[] = $part . $line_endings;
      }
    }

    // Merge all lines in the e-mail body and treat the result as safe markup.
    $message['body'] = Markup::create(implode($line_endings, array_map(function ($body) {
      // If the field contains no html tags we can assume newlines will need be
      // converted to <br>.
      if (strlen(strip_tags($body)) === strlen($body)) {
        $body = str_replace("\r", '', $body);
        $body = str_replace("\n", '<br>', $body);
      }
      return check_markup($body, 'full_html');
    }, $message['body'])));
    
    // Attempt to use the mail theme defined in MailSystem.
    if ($this->mailManager instanceof MailsystemManager) {
      $mail_theme = $this->mailManager->getMailTheme();
    }
    // Default to the active theme if MailsystemManager isn't used.
    else {
      $mail_theme = $this->themeManager->getActiveTheme()->getName();
    }

    $render = [
      '#theme' => $message['params']['theme'] ?? 'swiftmailer',
      '#message' => $message,
      '#is_html' => $is_html,
    ];

    if ($is_html) {
      $render['#attached']['library'] = ["$mail_theme/swiftmailer"];
    }

    $message['body'] = $this->renderer->renderPlain($render);

    if ($is_html) {
      // Process CSS from libraries.
      $assets = AttachedAssets::createFromRenderArray($render);
      $css = '';
      // Request optimization so that the CssOptimizer performs essential
      // processing such as @include.
      foreach ($this->assetResolver->getCssAssets($assets, TRUE) as $css_asset) {
        $css .= file_get_contents($css_asset['data']);
      }

      if ($css) {
        $message['body'] = (new CssToInlineStyles())->convert($message['body'], $css);
      }
    }
    else {
      // Convert to plain text.
      $message['body'] = (new Html2Text($message['body']))->getText();
    }
  }

}
