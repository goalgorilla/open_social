/**
 * @file
 * Contains the script that should be executed in the context the
 * actions/github-script action.
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
 * // Control which errors to test.
 * const errors = {
 *   hasConfigOverrides: false,
 *   hasHelpersAdded: false,
 * };
 *
 * const script = require('./.github/workflows/bestPracticesFeedback.js')
 * await script({github, context}, errors);
 * ```
 */

module.exports = async ({github, context}, { hasConfigOverrides, hasHelpersAdded }) => {
  const botMarker = '🤖 This is automatically generated feedback by your best practices bot.';

  let feedback = "";

  if (hasConfigOverrides) {
    feedback += "**Config Overrides Added**\n";
    feedback += "Your pull request adds one or more configuration override classes. Configuration overrides make maintenance of our product more difficult because they don't cooperate well with other modules that may want to change the same configuration. ";
    feedback += "You most likely want to use the [config_modify module](https://www.drupal.org/project/config_modify/) instead to add dynamic configuration when multiple optional modules that can work together are enabled.\n\n";
  }

  if (hasHelpersAdded) {
    feedback += "**Generic Helper Added**\n";
    feedback += "It looks like you're trying to add a helper class or other helper object. This often points to a [hasty abstraction](https://kentcdodds.com/blog/aha-programming), trying to deduplicate code that does not solve a specific enough task together with other code. ";
    feedback += "Re-evaluate whether this code should really be extracted out or whether a service with a more well defined purpose than 'helper' can be created.\n\n";
  }

  // Make some variables available so our code samples look just like the docs.
  const issue_number = context.issue.number;
  const owner = context.repo.owner;
  const repo = context.repo.repo;

  const response = await github.rest.issues.listComments({
    issue_number,
    owner,
    repo,
  });

  const existingComment = response.data.find(comment => comment.body.includes(botMarker));

  if (existingComment) {
    const comment_id = existingComment.id;

    if (feedback === "") {
      await github.rest.issues.deleteComment({
        owner,
        repo,
        comment_id,
      });
    }
    else {
      await github.rest.issues.updateComment({
        owner,
        repo,
        comment_id,
        body: `We've found some issues with best practices in this PR\n\n${feedback}${botMarker}`,
      });
    }
  }
  else if (feedback !== "") {
    await github.rest.issues.createComment({
      issue_number,
      owner,
      repo,
      body: `We've found some issues with best practices in this PR\n\n${feedback}${botMarker}`,
    });
  }
}
