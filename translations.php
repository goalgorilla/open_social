<?php
// phpcs:ignoreFile

/**
 * @file
 * A list of strings in older Open Social versions than current stable release.
 *
 * This file contains translatable strings that have been altered or changed in
 * the Open Social distribution. By wrapping these strings in the Drupal::t() or
 * Drupal::formatPlural function in this file, they will be picked up by the
 * POTX tool automatically. This ensures that they can be translated in the Open
 * Social translation workflow for older versions of Open Social.
 *
 * When adding texts to this file. Please specify in which version the string
 * was changed so that it can be cleaned up in the future when all platforms
 * are using at least that version.
 */

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

die('This file should not be run directly.');

// Changed in version X.Y
// new TranslatableMarkup('Example');
// new PluralTranslatableMarkup($count, '1 example', '@count examples');.

// Changed in version 7.2.
new TranslatableMarkup('Select / unselect all @count results in this view');
new TranslatableMarkup('Clear all selected members');
new TranslatableMarkup('A Reply-To address is the email address that receives messages sent from those who select Reply in their email clients.');

// These strings have been added because they were not being picked
// up by the POTX tool. This usually indicates an issue with configuration
// schema or a string not passed through `TranslatableMarkup`.
// These should be removed once the underlying issue is identified and fixed.
new TranslatableMarkup("Title & image");
new TranslatableMarkup("Names and profile image");
new TranslatableMarkup("Date & time");
new TranslatableMarkup("Attachments");
new TranslatableMarkup("Add attachment");
new TranslatableMarkup("Self introduction, expertise and interests");
new TranslatableMarkup("Phone number and location");
new TranslatableMarkup("Function and organization");
new TranslatableMarkup("Account information");
new TranslatableMarkup("Categories and terms used to tag content");
new TranslatableMarkup("Event types");
new TranslatableMarkup("Expertise");
new TranslatableMarkup("A users expertises");
new TranslatableMarkup("Interests");
new TranslatableMarkup("A users interests for their profile.");
new TranslatableMarkup("Profile organization tag");
new TranslatableMarkup("CM can tag a user and indicate that user is part of an organization.");
new TranslatableMarkup("Profile tag");
new TranslatableMarkup("CM can tag a user, giving options on filtering / searching users.");
new TranslatableMarkup("Topic types");
new TranslatableMarkup("-- Select action --");
new TranslatableMarkup("Enrollment options");
new TranslatableMarkup("Close report");
new TranslatableMarkup("on a");
new TranslatableMarkup("on the");
new TranslatableMarkup("Guest enrollments");
new TranslatableMarkup("Topic");
new TranslatableMarkup("Event");
new TranslatableMarkup("A Reply-To address is the email address that receives messages sent from those who select Reply in their email clients.");
new TranslatableMarkup("User settings");
new TranslatableMarkup("Execute action");
new TranslatableMarkup('Canceled "%action".');
new TranslatableMarkup("Manage Enrollments");
new TranslatableMarkup("Collaboration Settings");
new TranslatableMarkup("Reply-to");
new TranslatableMarkup("Selected @count entities:");
// Following plural strings are not translatable due to the @todo in
// _social_event_managers_action_batch_finish().
new PluralTranslatableMarkup(0, '1 selected enrollee has been exported successfully', '@count selected enrollees have been exported successfully');
new PluralTranslatableMarkup(0, '1 selected enrollee has not been exported successfully', '@count selected enrollees have not been exported successfully');
new PluralTranslatableMarkup(0, 'Your email has been sent to 1 selected enrollee successfully', 'Your email has been sent to @count selected enrollees successfully');
new PluralTranslatableMarkup(0, 'Your email has not been sent to 1 selected enrollee successfully', 'Your email has not been sent to @count selected enrollees successfully');
new PluralTranslatableMarkup(0, '1 selected enrollee has been removed from the event successfully', '@count selected enrollees have been removed from the event successfully');
new PluralTranslatableMarkup(0, '1 selected enrollee has not been removed from the event successfully', '@count selected enrollees have not been removed from the event successfully');
new PluralTranslatableMarkup(0, '1 selected member has been exported successfully', '@count selected members have been exported successfully');
new PluralTranslatableMarkup(0, '1 selected member has not been exported', '@count selected members have not been exported');
new PluralTranslatableMarkup(0, '1 selected member has been removed successfully', '@count selected members have been removed successfully');
new PluralTranslatableMarkup(0, '1 selected member has not been removed successfully', '@count selected members have been removed successfully');
new PluralTranslatableMarkup(0, 'The role of 1 selected member has been changed successfully', 'The role of @count selected members have been changed successfully');
new PluralTranslatableMarkup(0, 'The role of 1 selected member has not been changed successfully', 'The role of @count selected members have not been changed successfully');
new PluralTranslatableMarkup(0, 'Your email has been sent to 1 selected member successfully', 'Your email has been sent to @count selected members successfully');
new PluralTranslatableMarkup(0, 'Your email has not been sent to 1 selected member successfully', 'Your email has not been sent to @count selected members successfully');
new TranslatableMarkup("Remove");
new TranslatableMarkup("Export");
new TranslatableMarkup("Change the role");
