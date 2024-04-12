export const feedbackIntroduction = "Thanks for opening this Pull Request! To ensure Pull Requests are easy to find and understand for all our team-members we've established some standardised rules. While reviewing this pull request I've found the following issues, please resolve them.\n\n";
export const feedbackBotMarker = '🤖 This is an automatically produced message by the Open Social PR manager.';

// @todo Provide links to documentation for our different types.
export const feedbackMissingTypeLabel = "**Missing type label**\nThe pull request is missing a `type: ` label to indicate whether the pull request is trying fix a bug, introduce a new feature or do something else.\n\n";
export const feedbackDuplicateTypeLabel = "**Multiple type labels selected**\nThe pull request has multiple type labels selected which may indicate you're trying to do too much at once. Consider splitting up the pull request and select the single most appropriate type.\n\n";

// @todo Provide links to documentation for picking the correct priority.
export const feedbackMissingPriorityLabel = "**Missing priority label**\nThe pull request is missing a `prio: ` label which helps people prioritise contributing to open pull requests. Please choose a priority label appropriate for your pull request.\n\n";
export const feedbackDuplicatePriorityLabel = "**Duplicate priority label**\nYour pull request has multiple priority labels. Please select only a single priority and remove the other priority labels.\n\n";

export const feedbackMissingTeamLabel = "**Missing team label**\nYour pull request is missing a `team: ` label. The team label helps others understand what work they should focus on and which team they can ask questions about a specific PR.\n\n";
export const feedbackDuplicateTeamLabel = "**Duplicate team label**\nThe pull request hsa multiple `team: ` labels. Please select only the team label for the team that's responsible for the pull request and remove the others.\n\n";

export const feedbackMissingMilestone = "**Missing milestone**\nThe issue is missing a milestone. Milestones are important to help release managers know whether they might need to postpone a release for a critical issue and to communicate what fixes and new features are included in a release. Before merging, please select the appropriate milestone for this pull request.\n\n";
