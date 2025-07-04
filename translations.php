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

// Changed in version 11.0
new TranslatableMarkup('Open to join');
new TranslatableMarkup('users can join this @entity_type_id without approval.');
new TranslatableMarkup('Invite only');
new TranslatableMarkup('users can only join this @entity_type_id if they are added/invited by @entity_type_id managers.');
new TranslatableMarkup('Request to join');
new TranslatableMarkup('users can "request to join" this @entity_type_id which @entity_type_id managers approve/decline.');

// Changed in version 8.7, 9.4, 10.0
new TranslatableMarkup('
        Oops, there was an error. This may have happened for the following reasons: <br>
        - Invalid username/email and password combination. <br>
        - There has been more than one failed login attempt for this account. It is temporarily blocked. <br>
        - Too many failed login attempts from your computer (IP address). This IP address is temporarily blocked. <br> <br>
        To solve the issue, try using different login information, try again later, or <a href=":url">request a new password</a>');

// Change in version 8.3.
new TranslatableMarkup("Set wether event types field is required or not.");
new TranslatableMarkup("Determine wether users can upload documents to comments.");
new TranslatableMarkup("Set wether the tour is enabled or not.");

// Changed in version 8.x.
new TranslatableMarkup("Send mail");
new TranslatableMarkup("Can not send e-mail for %entity");
new TranslatableMarkup("Sent email to %recipient");
new TranslatableMarkup("Open to enroll - users can enroll for this event without approval");
new TranslatableMarkup("Request to enroll - users can 'request to enroll' for this event which event organisers approve/decline");
new TranslatableMarkup('Invite-only - users can only enroll for this event if they are added/invited by event organisers');
new TranslatableMarkup('Request to join - users can "request to join" this group which group managers approve/decline');
new TranslatableMarkup('Invite-only - users can only join this group if they are added/invited by group managers');
new TranslatableMarkup('Open to join - users can join this group without approval');
new TranslatableMarkup("Due to privacy concerns, we can't disclose the existence of registered email addresses. Please make sure the email address is entered correctly and try again.");
new TranslatableMarkup('The entered username already exists or has an incorrect format. Please try again.');

// Changed in version 7.2.
new TranslatableMarkup('Select / unselect all @count results in this view');
new TranslatableMarkup('Clear all selected members');
new TranslatableMarkup('A Reply-To address is the email address that receives messages sent from those who select Reply in their email clients.');

// Strings added because they were removed from configuration in the
// social_private_message module and are now set untranslated in an install
// hook.
new TranslatableMarkup("@interval hence");
new TranslatableMarkup("@interval ago");

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
new TranslatableMarkup("Group visibility");
new TranslatableMarkup("Who can see the group.");
new TranslatableMarkup("Group content visibility options");
new TranslatableMarkup("Choose the visibility options allowed for the group content.");
new TranslatableMarkup("Join methods");
new TranslatableMarkup("How can people join this group. Group managers can always add members directly, regardless of the chosen join method.");
new TranslatableMarkup("Someone commented on your content");
new TranslatableMarkup("You have a new comment on a post");
new TranslatableMarkup("You have a new reply to your comment");
new TranslatableMarkup("New content has been added to a group you are in");
new TranslatableMarkup("There is a new post in your stream");
new TranslatableMarkup("Your request to enroll to an event has been accepted");
new TranslatableMarkup("Someone joined one of your groups");
new TranslatableMarkup("Someone wants to enroll to your event");
new TranslatableMarkup("Someone reported content as inappropriate");
new TranslatableMarkup("You have a new enrollment to your event");
new TranslatableMarkup("You have been invited to an event");
new TranslatableMarkup("You have been added to an event");
new TranslatableMarkup("Someone commented on your content");
new TranslatableMarkup("Someone added content you might be interested in");
new TranslatableMarkup("Your request to join a group has been accepted");
new TranslatableMarkup("Someone wants to join your group");
new TranslatableMarkup("Your content has been liked");
new TranslatableMarkup("You have been mentioned");
new TranslatableMarkup("You have a new reply to a thread you were mentioned in");
new TranslatableMarkup("You have received a new private message");

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
new PluralTranslatableMarkup(0, 'Your email will be send to 1 selected enrollee', 'Your email will be send to @count selected enrollees');
new PluralTranslatableMarkup(0, 'Your email will be send to 1 selected member', 'Your email will be send to @count selected members');
new TranslatableMarkup("Remove");
new TranslatableMarkup("Export");
new TranslatableMarkup("Change the role");

// Strings added because they were removed from the creation/edit page of the
// "Custom content list block" block according to the new design.
new TranslatableMarkup('To make the list of topics more specific you can additionally configure more filters such as topic types, content tags and groups.');
new TranslatableMarkup('Autocomplete field with items from taxonomy list topic types.');
new TranslatableMarkup('Autocomplete field with items from taxonomy list content tags.');
new TranslatableMarkup('Autocomplete field with group names.');

// String added because original one was changed due to #3183708 issue.
new TranslatableMarkup('Changing the group may have impact on the <strong>visibility settings</strong>.');

// String added because it was removed from the Group Join Confirmation Page.
new TranslatableMarkup('By submitting this form you will become a member of the group. Please fill out any available fields to complete your membership information.');

// String added because activity view modes will display attachment count
// instead of links.
new PluralTranslatableMarkup(0, '1 attachment.', '@count attachments.');

// String added because original one was changed due to #3420469 issue.
new TranslatableMarkup('Resend invites for group members');

// String added because original one was changed due to #3439386 issue.
new TranslatableMarkup('Are you sure you want to send your email to to the following %count enrollees?');

// String added because original one was changed due to #3445024 issue.
new TranslatableMarkup('Find people by name or email address');
new TranslatableMarkup('You can enter or paste multiple entries separated by comma or semicolon');

// String added because original one was changed due to #3470967 issue.
new TranslatableMarkup('Registered users are verified immediately');
new TranslatableMarkup('New users will be required to validate their email address prior to logging into the site, and will be assigned a system-generated password. With this setting disabled, users will be logged in immediately upon registering, and may select their own passwords during registration.');

// Strings added to replace the old form add titles due to #PROD-30735 issue.
new TranslatableMarkup('Create an album');
new TranslatableMarkup('Create a book page');
new TranslatableMarkup('Create an event');
new TranslatableMarkup('Create a landing page');
new TranslatableMarkup('Create a page');
new TranslatableMarkup('Create an idea');
new TranslatableMarkup('Create a topic');
new TranslatableMarkup('Create a challenge');
new TranslatableMarkup('Create a group');
new TranslatableMarkup('Create a flexible group');
new TranslatableMarkup('Create a course');
new TranslatableMarkup('Create a basic course');
new TranslatableMarkup('Create an advanced course');
new TranslatableMarkup('Create a post');
new TranslatableMarkup('Create a photo');
new TranslatableMarkup('Create a file');
new TranslatableMarkup('Create a link');
new TranslatableMarkup('Create a folder');
new TranslatableMarkup('Create an organization');

// Strings added to replace the old ones due to ticket PROD-32584.
new PluralTranslatableMarkup(0, '1 membership request to group: :group_title', '@count membership requests to group: :group_title');
new TranslatableMarkup('Approve membership request for the group @group_title');
new TranslatableMarkup('Reject membership request for the group @group_title');
new TranslatableMarkup('Approve Membership');
new TranslatableMarkup('Reject Membership');

// Strings added to replace the old ones due to ticket PROD-33038.
new PluralTranslatableMarkup(0, '@count new member joined the group.', '@count new members joined the group.');
new PluralTranslatableMarkup(0, 'Choose which group roles to assign to the member you selected', 'Choose which group roles to assign to the @count members you selected');
new PluralTranslatableMarkup(0, '1 group invite', '@count group invites');
new PluralTranslatableMarkup(0, 'User with @error_message e-mail already a member of this group', 'Users with: @error_message e-mails already members of this group');
new TranslatableMarkup('Send a welcome message to new group members');
new TranslatableMarkup('Group roles');
