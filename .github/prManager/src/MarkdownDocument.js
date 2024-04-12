export class MarkdownDocument {

  constructor(tokens) {
    this.tokens = tokens;
  }

  /**
   * Whether the document is empty.
   *
   * The document is empty if it contains no tokens or all tokens are of type
   * space.
   *
   * @returns {boolean}
   */
  isEmpty() {
    if (this.tokens === []) {
      return true;
    }

    for (const token of this.tokens) {
      if (token.type !== "space") {
        return false;
      }
    }

    return true;
  }

  /**
   * Get the tokens for a certain section of the document.
   *
   * Will return all tokens that follow the token with with matching title of
   * the required depth until a heading is found with the same or higher level
   * (e.g. heading 1 is above heading 2) or the end of the document.
   *
   * @param title
   *   The title of the section to fetch.
   * @param depth
   *   The heading level to extract.
   *
   * @returns {MarkdownDocument}
   *   The extracted section.
   */
  findSection(title, depth = 2) {
    let start = null;
    let section = [];
    for (const token of this.tokens) {
      if (start === null) {
        if (token.type === "heading" && token.depth === depth && token.text === title) {
          start = token;
        }
      }
      else if (token.type === "heading" && token.depth <= depth) {
        break;
      }
      else {
        section.push(token);
      }
    }

    return new MarkdownDocument(section);
  }

  /**
   * Convert the document back to markdown.
   *
   * @returns {string}
   *   A markdown string.
   */
  toMarkdown() {
    let output = '';

    for (const token of this.tokens) {
      output += token.raw;
    }

    return output;
  }

  /**
   * Find all links in a document.
   *
   * @returns {string[]}
   */
  getLinks() {
    let links = [];

    for (const token of this.tokens) {
      if (token.type === "link") {
        links.push(token.href);
      }
      else if (typeof token.tokens !== "undefined" && token.tokens !== []) {
        const nestedLinks = (new MarkdownDocument(token.tokens)).getLinks();
        if (nestedLinks !== []) {
          links.push(...nestedLinks);
        }
      }
    }

    return links;
  }
}
