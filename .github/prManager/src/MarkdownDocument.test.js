import { MarkdownDocument } from "./MarkdownDocument.js";
import { marked } from "marked";

test('isEmpty returns true for []', () => {
  const document = new MarkdownDocument([]);
  expect(document.isEmpty()).toBe(true);
})

test('isEmpty returns true for space only documents', () => {
  const document = new MarkdownDocument([
    { type: 'space' },
    { type: 'space' },
  ]);
  expect(document.isEmpty()).toBe(true);
})

test('toMarkdown returns the unmodified string', () => {
  const markdown = `
    # Test
    Foo bar baz.
  `;
  const document = new MarkdownDocument(marked.lexer(markdown));
  expect(document.toMarkdown()).toBe(markdown);
})

test("findSection returns an empty document if the section isn't found", () => {
  const document = new MarkdownDocument(marked.lexer(`
   # Heading
   This is some text
   ## Foo
   Underneath Foo
  `));
  expect(document.findSection("Bar").isEmpty()).toBe(true);
})

test("findSection looks for depth 2 by default", () => {
  const document = new MarkdownDocument(marked.lexer(`
   # Foo
   Level 1 text
   ## Foo
   Level 2 text
  `));
  expect(document.findSection("Foo").toMarkdown()).toBe("   Level 2 text\n  ");
})

test("findSection allows looking for a different heading depth", () => {
  const document = new MarkdownDocument(marked.lexer(`
   # Foo
   Level 1 text
   ## Foo
   Level 2 text
  `));
  expect(document.findSection("Foo", 1).toMarkdown()).toBe(`   Level 1 text
   ## Foo
   Level 2 text
  `);
})

test('getLinks returns the url for all links in the document', () => {
  const document = new MarkdownDocument(marked.lexer(`
   Top level link in a paragraph: http://example.com/one

   # Foo
   Link under a heading https://example.com/two

   ## Nested more
   [Link with text](https://example.com/three)
  `));
  expect(document.getLinks()).toEqual([
    "http://example.com/one",
    "https://example.com/two",
    "https://example.com/three",
  ]);
})
