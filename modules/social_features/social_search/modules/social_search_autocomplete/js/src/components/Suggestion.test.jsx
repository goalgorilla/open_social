import React from "react";
import renderer from "react-test-renderer";
import Suggestion from "./Suggestion";

describe("Suggestion", () => {
  it("renders a topic suggestion", () => {
    const tree = renderer
      .create(
        <Suggestion
          query="open"
          type="Topic"
          label="Open Social is great for customisable communities"
          url="http://example.com/node/1"
        />
      )
      .toJSON();

    expect(tree).toMatchSnapshot();
  });

  it("renders an event with a tag", () => {
    const tree = renderer
      .create(
        <Suggestion
          query="launch"
          type="Event"
          label="Open Social launch party"
          url="http://example.com/node/2"
          tags={["You are enrolled to this event"]}
        />
      )
      .toJSON();

    expect(tree).toMatchSnapshot();
  });
});
