import { jest } from "@jest/globals";
import script from './main.js';
import {
  feedbackBotMarker, feedbackDuplicatePriorityLabel, feedbackDuplicateTeamLabel, feedbackDuplicateTypeLabel,
  feedbackIntroduction,
  feedbackMissingMilestone, feedbackMissingPriorityLabel, feedbackMissingTeamLabel,
  feedbackMissingTypeLabel
} from "./constants.js";

const labels = {
  type: {
    bug: {
      name: "type: bug",
    },
    feature: {
      name: "type: feature",
    },
  },
  status: {
    needs_review: {
      name: "status: needs review",
    },
    needs_backport: {
      name: "status: needs backport",
    },
  },
  prio: {
    critical: {
      name: "prio: critical",
    },
    high: {
      name: "prio: high",
    },
    medium: {
      name: "prio: medium",
    },
    low: {
      name: "prio: low",
    },
  },
  team: {
    guardians: {
      name: "team: guardians",
    },
    orbiters: {
      name: "team: orbiters",
    },
  },
};

const author_association = {
  member: "MEMBER",
  collaborator: "COLLABORATOR",
  contributor: "CONTRIBUTOR",
  first_time_contributor: "FIRST_TIME_CONTRIBUTOR",
  owner: "OWNER",
  none: "NONE",
};

const prBase = {
  "data": {
    "number": 1337,
    "title": "",
    "user": {
      "login": "foobar",
    },
    "labels": [],
    "state": "open",
    "locked": false,
    "assignee": null,
    "assignees": [],
    "milestone": {
      "id": 10704408,
      "node_id": "MI_kwDOA8vkVc4Ao1YY",
      "number": 392,
      "title": "12.4.0",
      "description": "",
      "open_issues": 3,
      "closed_issues": 6,
      "state": "open",
      "created_at": "2024-03-19T12:13:50Z",
      "updated_at": "2024-04-11T16:01:04Z",
      "due_on": null,
      "closed_at": null
    },
    "comments": 2,
    "created_at": "2024-04-10T14:16:34Z",
    "updated_at": "2024-04-10T16:42:08Z",
    "closed_at": null,
    "author_association": "NONE",
    "active_lock_reason": null,
    "draft": false,
    "body": "## Issue Links\nhttps://getopensocial.atlassian.net/browse/PROD-29281",
    "closed_by": null,
    "performed_via_github_app": null,
    "state_reason": null
  }
};

const context = {
  issue: {
    issue_number: prBase.data.number,
  },
  repo: {
    owner: "goalgorilla",
    repo: "open_social",
  }
};

test("it posts a comment when a type label isn't present", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.prio.medium,
        labels.team.guardians,
        labels.status.needs_review,
      ],
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackMissingTypeLabel}${feedbackBotMarker}`,
  });
})

test("it posts a comment when a multiple type labels are present", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.type.bug,
        labels.type.feature,
        labels.prio.medium,
        labels.team.guardians,
        labels.status.needs_review,
      ],
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackDuplicateTypeLabel}${feedbackBotMarker}`,
  });
})

test("it posts a comment when a prio label isn't present", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.type.bug,
        labels.team.guardians,
        labels.status.needs_review,
      ],
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackMissingPriorityLabel}${feedbackBotMarker}`,
  });
})

test("it posts a comment when a multiple priority labels are present", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.type.bug,
        labels.prio.medium,
        labels.prio.critical,
        labels.team.guardians,
        labels.status.needs_review,
      ],
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackDuplicatePriorityLabel}${feedbackBotMarker}`,
  });
})

test("it posts a comment when a team label isn't present", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.type.bug,
        labels.prio.medium,
        labels.status.needs_review,
      ],
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackMissingTeamLabel}${feedbackBotMarker}`,
  });
})

test("it posts a comment when a multiple team labels are present", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.type.bug,
        labels.prio.medium,
        labels.team.guardians,
        labels.team.orbiters,
        labels.status.needs_review,
      ],
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackDuplicateTeamLabel}${feedbackBotMarker}`,
  });
})

test("it posts a comment when a milestone isn't selected", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.type.bug,
        labels.team.guardians,
        labels.prio.medium,
        labels.status.needs_review,
      ],
      milestone: null,
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${feedbackMissingMilestone}${feedbackBotMarker}`,
  });
})

test("it combines feedback for multiple errors", async () => {
  const mockPr = {
    ...prBase,
    data: {
      ...prBase.data,
      labels: [
        labels.team.guardians,
        labels.prio.medium,
        labels.status.needs_review,
      ],
      milestone: null,
    },
  }
  const github = {
    rest: {
      issues: {
        get: jest.fn(() => mockPr),
        createComment: jest.fn(() => {}),
        listComments: jest.fn(() => ({ data: [] })),
      },
    },
  };

  await script({ github, context });

  const expectedFeedback = `${feedbackMissingTypeLabel}${feedbackMissingMilestone}`;

  expect(github.rest.issues.get).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledTimes(1);
  expect(github.rest.issues.createComment).toHaveBeenCalledWith({
    owner: context.repo.owner,
    repo: context.repo.repo,
    issue_number: context.issue.issue_number,
    body: `${feedbackIntroduction}${expectedFeedback}${feedbackBotMarker}`,
  });
})
