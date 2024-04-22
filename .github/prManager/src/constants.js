export const feedbackIntroduction = "Thanks for opening this Pull Request! To ensure Pull Requests are easy to find and understand for all our team-members we've established some standardised rules. While reviewing this pull request I've found the following issues, please resolve them.\n\n";
export const feedbackBotMarker = '🤖 This is an automatically produced message by the Open Social PR manager.';

// @todo Provide links to documentation for our different types.
export const feedbackMissingTypeLabel = "**Missing type label**\nThe pull request is missing a `type: ` label to indicate whether the pull request is trying fix a bug, introduce a new feature or do something else.\n\n";
export const feedbackDuplicateTypeLabel = "**Multiple type labels selected**\nThe pull request has multiple type labels selected which may indicate you're trying to do too much at once. Consider splitting up the pull request and select the single most appropriate type.\n\n";

// @todo Provide links to documentation for picking the correct priority.
export const feedbackMissingPriorityLabel = "**Missing priority label**\nThe pull request is missing a `prio: ` label which helps people prioritise contributing to open pull requests. Please choose a priority label appropriate for your pull request.\n\n";
export const feedbackDuplicatePriorityLabel = "**Multiple priority labels selected**\nYour pull request has multiple priority labels. Please select only a single priority and remove the other priority labels.\n\n";

export const feedbackMissingTeamLabel = "**Missing team label**\nYour pull request is missing a `team: ` label. The team label helps others understand what work they should focus on and which team they can ask questions about a specific PR.\n\n";
export const feedbackDuplicateTeamLabel = "**Multiple team labels selected**\nThe pull request has multiple `team: ` labels. Please select only the team label for the team that's responsible for the pull request and remove the others.\n\n";

export const feedbackMissingMilestone = "**Missing milestone**\nThe issue is missing a milestone. Milestones are important to help release managers know whether they might need to postpone a release for a critical issue and to communicate what fixes and new features are included in a release. Before merging, please select the appropriate milestone for this pull request.\n\n";

export const feedbackInvalidTitle = "**Invalid Title**\nThe title you've provided for this pull request does not follow the expected title formats. Consistent titles make origins and purposes of issues easy to understand. Please use one of the following title formats:\n- `PROD-NNN: ` for a PR related to an Open Social Jira issue\n- `Issue #NNN: ` for a PR originating from a Drupal.org issue.\n- `Internal: ` for a repository maintenance PR without matching Jira ticket (prefer creating a ticket to track your work).\n- `Updates: ` for a PR that updates modules (e.g. by dependabot).\n- `Hotfix: ` in case it's really important something gets fixed now.\n\nChoose the proper prefix and write a title that tells developers without other context what your PR is about. Do NOT end your title with a period.\n\n";
export const feedbackDrupalTitleForJira = "**Drupal Title format for Jira issue**\nYou're using the `Issue #` format which is intended to show a PR originates from Drupal.org. However, you're using this with a Jira issue. Remove the `Issue #` part and start your title directly with `PROD-` instead.\n\n";
