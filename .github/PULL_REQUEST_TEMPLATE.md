## Problem
*[Required] Describe the problem you're trying to solve, this should motivate why the changes you're proposing are needed.*

## Solution
*[Required] Describe the solution you've created, elaborate on any technical choices you've made. Why is this the right solution and is a different solution not the right one? What is the reasoning behind the chosen solution?*

## Issue tracker
*[Required] Paste a link to the drupal.org issue queue item. If any other issue trackers were used, include links to those too.*

## Theme issue tracker
*[Required if applicable] Paste a link to the drupal.org theme issue queue item, either from [socialbase](https://www.drupal.org/project/socialbase) or [socialblue](https://www.drupal.org/project/socialblue). If any other issue trackers were used, include links to those too.*

## How to test
*[Required] For example*
- [ ] Using version X.Y.Z of Open Social with the example module enabled
- [ ] As a sitemanager
- [ ] Try to enable the option B on screen c/d/e
- [ ] When saving I expect the result to be F but instead see G.
- [ ] The expected result F is attained when repeating the steps with this fix applied.

## Definition of done
### Before merge
- [ ] Code/peer review is completed
- [ ] All commit messages are [clear and clean](https://open-social.slite.com/app/docs/DnmermZDIx_0OQ). If applicable a rebase was performed
- [ ] All automated tests are green
- [ ] Functional/manual tests of the acceptance criteria are approved
- [ ] All acceptance criteria were met
- [ ] If applicable (I.E. new hook_updates) the update path from previous versions (major and minor versions) are tested
- [ ] This pull request has all required labels (team/type/priority)
- [ ] This pull request has a milestone
- [ ] This pull request has an assignee (if applicable)
- [ ] Any front end changes are tested on all major browsers
- [ ] New UI elements, or changes on UI elements are approved by the design team
- [ ] New features, or feature changes are approved by the product owner

### After merge
- [ ] Code is tested on all branches that it has been cherry-picked
- [ ] Update hook number might need adjustment, make sure they have the correct order
- [ ] The Drupal.org ticket(s) are updated according to this pull request status

## Screenshots
*[Required if new feature, and if applicable] If this Pull Request makes visual changes then please include some screenshots that show what has changed here. A before and after screenshot helps the reviewer determine what changes were made.*

## Release notes
*[Required if new feature, and if applicable] A short summary of the changes that were made that can be included in release notes.*

## Change Record
*[Required if applicable] If this Pull Request changes the way that developers should do things or introduces a new API for developers then a change record to document this is needed. Please provide a draft for a change record or a link to an unpublished change record below. Existing change records can be consulted as example. Please provide a draft for a change record or a link to an unpublished change record below. [Existing change records](https://www.drupal.org/list-changes/social) can be consulted as example.*

## Translations
*[Optional]Translatable strings are always extracted from the latest development branch. To ensure translations remain available for platforms running older versions of Open Social the original string should be added to `translations.php` when it's changed or removed.*
- [ ] Changed or removed source strings are added to the `translations.php` file.
