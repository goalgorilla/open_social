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
  feedbackDuplicateTypeLabel, feedbackMissingMilestone, feedbackMissingPriorityLabel,
  feedbackMissingTeamLabel,
  feedbackMissingTypeLabel
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

  await issue.provideFeedback(feedback);
}






