import { marked } from "marked";
import { MarkdownDocument } from "./MarkdownDocument.js";
import { feedbackBotMarker, feedbackIntroduction } from "./constants.js";

/**
 * Contains the data for a GitHub issue and method of working with it.
 */
export class Issue {

  bodyDocument = null;

  constructor(github, context, issue) {
    this.github = github;
    this.context = context;
    this.issue = issue;
  }

  /**
   * Whether this issue is a Pull Request.
   *
   * GitHub's Pull Requests are issues but not all issues are pull requests.
   *
   * @returns {boolean}
   *   True if the issue is a pull request, false otherwise.
   */
  isPr() {
    return (this.issue.pull_request ?? null) !== null;
  }

  /**
   * Get the title of the issue.
   *
   * @returns {string}
   */
  getTitle() {
    return this.issue.title;
  }

  /**
   * Get the body of this issue as parsed Markdown document.
   *
   * @returns {MarkdownDocument}
   *   The Markdown document for the body of this issue.
   */
  getBody() {
    if (this.bodyDocument === null) {
      this.bodyDocument = new MarkdownDocument(marked.lexer(this.issue.body));
    }

    return this.bodyDocument;
  }

  /**
   * Get the raw body of this issue in Markdown.
   *
   * @returns {string}
   */
  getBodyRaw() {
    return this.issue.body;
  }

  /**
   * Get the labels for this issue that indicate its type.
   *
   * @returns {*}
   */
  getTypeLabels() {
    return this.issue.labels.filter(label => label.name.startsWith("type: "));
  }

  /**
   * Get the labels for this issue that indicate the team that owns it.
   *
   * @returns {*}
   */
  getTeamLabels() {
    return this.issue.labels.filter(label => label.name.startsWith("team: "));
  }

  /**
   * Get the labels for this issue that indicate its priority.
   *
   * @returns {*}
   */
  getPriorityLabels() {
    return this.issue.labels.filter(label => label.name.startsWith("prio: "));
  }

  /**
   * Get the milestone assigned to this issue if any.
   *
   * @returns {null|*}
   */
  getMilestone() {
    return this.issue.milestone;
  }

  /**
   * Whether this issue is opened by a first time contributor.
   *
   * @returns {boolean}
   */
  isByFirstTimeContributor() {
    return this.issue.author_association === "FIRST_TIME_CONTRIBUTOR";
  }

  /**
   * Whether this issue is opened by an internal contributor.
   *
   * Internal contributors are employees of the organization.
   *
   * @returns {boolean}
   */
  isByInternalContributor() {
    return this.issue.author_association === "OWNER" || this.issue.author_association === "MEMBER";
  }

  /**
   * Whether this issue is opened by a collaborating contributor.
   *
   * A collaborating contributor might not be an employee of the organization
   * but has been given special access to the repository the issue is in.
   *
   * @returns {boolean}
   */
  isByCollaboratingContributor() {
    return this.issue.author_association === "COLLABORATOR";;
  }

  /**
   * Whether this pull request is a draft.
   *
   * @returns {boolean}
   */
  isDraft() {
    return this.issue.draft ?? false;
  }

  /**
   * Post a comment with feedback to the issue or update an existing comment.
   *
   * If feedback has not empty will create a new comment if none exists or
   * update the previously posted comment.
   * If feedback is empty then any previously posted comments will be cleaned
   * up.
   *
   * @param {string} feedback
   *   The feedback to provide or an empty string of there is no feedback.
   */
  async provideFeedback(feedback) {
    const { owner, repo } = this.context;
    const issue_number = this.issue.number;
    const body = `${feedbackIntroduction}${feedback}${feedbackBotMarker}`;

    const response = await this.github.rest.issues.listComments({
      issue_number,
      owner,
      repo,
    });

    const existingComment = response.data.find(comment => comment.body.includes(feedbackBotMarker));

    if (existingComment) {
      const comment_id = existingComment.id;

      if (feedback === "") {
        await this.github.rest.issues.deleteComment({
          owner,
          repo,
          comment_id,
        });
      }
      else {
        await this.github.rest.issues.updateComment({
          owner,
          repo,
          comment_id,
          body,
        });
      }
    }
    else if (feedback !== "") {
      await this.github.rest.issues.createComment({
        issue_number,
        owner,
        repo,
        body,
      });
    }
  }

}
