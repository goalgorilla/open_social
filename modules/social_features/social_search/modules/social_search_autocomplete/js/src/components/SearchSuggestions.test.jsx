import React from "react";
import renderer from "react-test-renderer";
import SearchSuggestions from "./SearchSuggestions";

describe("SearchSuggestions", () => {
  const suggestions = [
    {
      type: "Topic",
      label: "Open Social is great for customisable communities",
      url: "http://example.com/node/1"
    },
    {
      type: "Event",
      label: "Open Social launching party",
      url: "http://example.com/node/2",
      tags: ["You are enrolled to this event"]
    },
    {
      type: "Event",
      label: "GoalGorilla anniversary",
      url: "http://example.com/node/3"
    }
  ];

  it("renders nothing when no query is provided", () => {
    const tree = renderer.create(
      <SearchSuggestions
        searchBase="http://example.com/search/base"
        suggestions={suggestions}
      />
    );

    expect(tree.toJSON()).toBeNull();
  });

  it("renders nothing when no suggestions are provided", () => {
    const tree = renderer.create(
      <SearchSuggestions
        searchBase="http://example.com/search/base"
        query="social"
      />
    );

    expect(tree.toJSON()).toBeNull();
  });

  it("renders a list of suggestions", () => {
    const tree = renderer
      .create(
        <SearchSuggestions
          searchBase="http://example.com/search/base"
          query="open"
          suggestions={suggestions}
        />
      )
      .toJSON();

    expect(tree).toMatchSnapshot();
  });
});
