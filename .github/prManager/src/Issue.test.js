import { Issue } from "./Issue.js";
import { jest } from "@jest/globals";
import { feedbackBotMarker, feedbackIntroduction } from "./constants.js";

const context = {
  owner: "goalgorilla",
  repo: "open_social",
};

const issueData = {
  number: 1337,
};

test("provideFeedback does nothing without feedback and no previous comment", async () => {
  const github = {
    rest: {
      issues: {
        createComment: jest.fn(() => {}),
        updateComment: jest.fn(() => {}),
        deleteComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  const issue = new Issue(github, context, issueData);

  await issue.provideFeedback("");

  expect(github.rest.issues.listComments).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.updateComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.deleteComment).toHaveBeenCalledTimes(0);
});

test("provideFeedbackc cleans up a previous comment when provided with no feedback", async () => {
  const comment_id = 2674;
  const github = {
    rest: {
      issues: {
        createComment: jest.fn(() => {}),
        updateComment: jest.fn(() => {}),
        deleteComment: jest.fn(() => {}),
        listComments: jest.fn(
          () => ({
            data: [{
              id: comment_id,
              body: feedbackBotMarker,
            }],
          })
        ),
      },
    },
  };

  const issue = new Issue(github, context, issueData);

  await issue.provideFeedback("");

  expect(github.rest.issues.listComments).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.updateComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.deleteComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.deleteComment).toHaveBeenCalledWith({
    owner: context.owner,
    repo: context.repo,
    comment_id,
  });
});

test("provideFeedback posts a new comment when none exists", async () => {
  const github = {
    rest: {
      issues: {
        createComment: jest.fn(() => {}),
        updateComment: jest.fn(() => {}),
        deleteComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  const issue = new Issue(github, context, issueData);

  const expectedFeedback = "Test comment";

  await issue.provideFeedback(expectedFeedback);

  expect(github.rest.issues.listComments).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.updateComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.deleteComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.owner,
    repo: context.repo,
    issue_number: issueData.number,
    body: `${feedbackIntroduction}${expectedFeedback}${feedbackBotMarker}`,
  })
});

test("provideFeedback updates an existing comment when there is one", async () => {
  const comment_id = 2674;
  const github = {
    rest: {
      issues: {
        createComment: jest.fn(() => {}),
        updateComment: jest.fn(() => {}),
        deleteComment: jest.fn(() => {}),
        listComments: jest.fn(
          () => ({
            data: [{
              id: comment_id,
              body: feedbackBotMarker,
            }],
          })
        ),
      },
    },
  };

  const issue = new Issue(github, context, issueData);

  const expectedFeedback = "Test comment";

  await issue.provideFeedback(expectedFeedback);

  expect(github.rest.issues.listComments).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.deleteComment).toHaveBeenCalledTimes(0);
  expect(github.rest.issues.updateComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.updateComment).toHaveBeenCalledWith({
    owner: context.owner,
    repo: context.repo,
    comment_id,
    body: `${feedbackIntroduction}${expectedFeedback}${feedbackBotMarker}`,
  });
});
