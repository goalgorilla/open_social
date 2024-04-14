/**
 * @file
 * Contains the script that should be executed in the context the
 * actions/github-script action.
 *
 * The Open Social PR manager will enforce pull request quality rules and
 * automate labeling based on actions taken.
 *
 * To try this file locally, create a personal access token with `repo`
 * permission and create a custom JavaScript file in the root of this repository
 * with the following code, replace the placeholder values.
 *
 * ```
 * import { Octokit } from "https://esm.sh/@octokit/rest";
 *
 * const context = {
 *   issue: {
 *     number: YOUR_TEST_ISSUE_NUMBER,
 *   },
 *   repo: {
 *     owner: YOUR_ORGANIZATION,
 *     repo: YOUR_TEST_REPOSITORY,
 *   }
 * }
 *
 * const github = new Octokit({
 *   auth: TOKEN_HERE,
 *   userAgent: 'Open Social Best Practice Development',
 * });
 *
 * const script = require('./.github/workflows/prManager.js')
 * await script({github, context});
 * ```
 */
import { Issue } from "./Issue.js";
import {
  feedbackDuplicatePriorityLabel,
  feedbackDuplicateTeamLabel,
  feedbackDuplicateTypeLabel,
  feedbackMissingMilestone,
  feedbackMissingPriorityLabel,
  feedbackMissingTeamLabel,
  feedbackMissingTypeLabel,
  feedbackInvalidTitle,
  feedbackDrupalTitleForJira,
} from "./constants.js";

export default async ({github, context, log = console.log}) => {
  // Make some variables available so our code samples look just like the docs.
  const issue_number = context.issue.number;
  const owner = context.repo.owner;
  const repo = context.repo.repo;
  const actor = context.actor;

  // Dependabot is not a human so it doesn't adhere to our standards.
  if (actor === "dependabot") {
    return;
  }

  const issueResponse = await github.rest.issues.get({
    owner,
    repo,
    issue_number,
  });

  // Something went wrong getting the issue data.
  if (issueResponse === null) {
    return;
  }

  const issue = new Issue(github, { owner, repo }, issueResponse.data);

  const issueTrackerLinks = issue.getBody().findSection("Issue tracker").getLinks();

  const typeLabels = issue.getTypeLabels();
  const priorityLabels = issue.getPriorityLabels();
  const teamLabels = issue.getTeamLabels();

  let feedback = '';

  if (typeLabels.length === 0) {
    feedback += feedbackMissingTypeLabel;
  }
  else if (typeLabels.length > 1) {
    feedback += feedbackDuplicateTypeLabel;
  }

  if (teamLabels.length === 0) {
    feedback += feedbackMissingTeamLabel;
  }
  else if (teamLabels.length > 1) {
    feedback += feedbackDuplicateTeamLabel;
  }

  if (priorityLabels.length === 0) {
    feedback += feedbackMissingPriorityLabel;
  }
  else if (priorityLabels.length > 1) {
    feedback += feedbackDuplicatePriorityLabel;
  }

  if (issue.getMilestone() === null) {
    feedback += feedbackMissingMilestone;
  }

  // We special case people setting `Issue #PROD-NNN` because we want more
  // specific feedback.
  if (isDrupalTitleForJira(issue.getTitle())) {
    feedback += feedbackDrupalTitleForJira;
  }
  else if (!isValidTitle(issue.getTitle())) {
    feedback += feedbackInvalidTitle;
  }

  await issue.provideFeedback(feedback);
}

/**
 * Check whether the title adheres to our standards.
 *
 * Whether the title correctly has the Jira ticket or Drupal.org issue,
 * or one of the exempt prefix.
 *
 * @param title
 *   The title.
 * @returns {boolean}
 *   Whether it adheres to our standards.
 */
function isValidTitle(title) {
  const prefixes = ["Internal: ", "Updates: ", "Hotfix: "];

  // Start with a specific prefix, don't end in a dot and have some text other than the prefix.
  for (const prefix of prefixes) {
    if (title.startsWith(prefix) && title.trim() !== prefix.trim() && !title.endsWith(".")) {
      return true;
    }
  }

  // "PROD-NNN: Some mandatory text".
  if (/^PROD-\d+: /.test(title) && !/^PROD-\d+:$/.test(title.trim()) && !title.endsWith(".")) {
    return true;
  }

  // Issue #NNNN: Some mandatory text".
  if (/^Issue #\d+: /.test(title) && !/^Issue #\d+:$/.test(title.trim()) && !title.endsWith(".")) {
    return true;
  }
  // Todo: PROD-NNN: Issue #NN: Issue #PROD-NNN:

  return false;
}

/**
 * Whether the title treats a Jira ticket as Drupal.org issue.
 *
 * @param title
 *   The title.
 * @returns {boolean}
 *   Whether the Jira ticket is put in the Drupal issue format.
 */
function isDrupalTitleForJira(title) {
  return /^Issue #PROD-\d+/.test(title);
}




