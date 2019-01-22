import React from "react";
import PropTypes from "prop-types";

/**
 * Escapes a string for use as literal RegExp value.
 *
 * @param {string} string
 *   The user string to escape.
 * @return {string}
 *   The string with all special characters escaped.
 */
function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"); // $& means the whole matched string
}

/**
 * Returns the target string with <b> tags around the highlight word.
 *
 * @param {string} highlight
 *   The word(s) to highlight in the target.
 * @param {string} target
 *   The string to process for highlighting.
 * @return {array}
 *   An array to make up the string that will properly be interpreted by React.
 */
function highlightText(highlight, target) {
  const loweredHighlight = highlight.toLowerCase();

  // Create a capturing regular expression for splitting. This ensures that when
  // we iterate we don't lose the search string.
  const search = new RegExp(`(${escapeRegExp(highlight)})`, "gi");
  const parts = target.split(search);

  // Assemble the string again, highlighting our search term.
  const text = [];

  // eslint-disable-next-line
  for (const i in parts) {
    if (parts[i].toLowerCase() === loweredHighlight) {
      text.push(<b key={i}>{parts[i]}</b>);
    } else {
      text.push(parts[i]);
    }
  }

  return text;
}

function Suggestion(props) {
  const { query, type, label, url, tags } = props;

  const highlightedLabel = highlightText(query, label);

  const markupTags = [];

  // eslint-disable-next-line
  for (const i in tags) {
    markupTags.push(
      <span
        key={i}
        className="search-suggestion__tag badge badge-default badge--pill"
      >
        {tags[i]}
      </span>
    );
  }

  return (
    <a className="search-suggestion" href={url} tabIndex="0">
      <div className="search-suggestion__type">{type}</div>
      <div className="search-suggestion__body">
        <div className="search-suggestion__label">{highlightedLabel}</div>
        {tags.length ? (
          <div className="search-suggestion__tags">{markupTags}</div>
        ) : null}
      </div>
    </a>
  );
}

Suggestion.propTypes = {
  query: PropTypes.string.isRequired,
  type: PropTypes.string.isRequired,
  label: PropTypes.string.isRequired,
  url: PropTypes.string.isRequired,
  tags: PropTypes.arrayOf(PropTypes.string)
};

Suggestion.defaultProps = {
  tags: []
};

export default Suggestion;
