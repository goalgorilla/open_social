import React, { useContext } from "react";
import PropTypes from "prop-types";
import Suggestion from "./Suggestion";
import TranslationContext from "../TranslationContext";

function SearchSuggestions(props) {
  const { searchBase, query, suggestions } = props;

  // The TranslationContext exposes the `t` and `formatPlural` function.
  // The name `Drupal` is chosen so that code analysis from POTX works.
  const Drupal = useContext(TranslationContext);

  if (!query.length || !suggestions.length) {
    return null;
  }

  const results = [];

  // eslint-disable-next-line
  for (const i in suggestions) {
    results.push(<Suggestion key={i} query={query} {...suggestions[i]} />);
  }

  const searchUrl = `${searchBase}/${encodeURIComponent(query)}`;

  return (
    <React.Fragment>
      <div className="search-suggestions">{results}</div>
      <div className="search-suggestions__all">
        <a href={searchUrl} className="btn btn-default btn-raised">
          {Drupal.t('See all results')}
        </a>
      </div>
    </React.Fragment>
  );
}

SearchSuggestions.propTypes = {
  searchBase: PropTypes.string.isRequired,
  query: PropTypes.string,
  suggestions: PropTypes.arrayOf(
    PropTypes.shape({
      type: PropTypes.string.isRequired,
      label: PropTypes.string.isRequired,
      url: PropTypes.string.isRequired,
      tags: PropTypes.arrayOf(PropTypes.string),
    })
  ),
};

SearchSuggestions.defaultProps = {
  query: "",
  suggestions: [],
};

export default SearchSuggestions;
