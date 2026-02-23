# Explanation

Background and conceptual articles that explain why the library is designed the way it is.

## Articles

### [Immutability](immutability.md)

Why all nodes are immutable and what that means for how you work with the library. Covers the benefits of immutable data structures and the trade-offs involved.

### [Forward Compatibility](forward-compatibility.md)

How the library preserves unknown content—node types, fields, and bitmask bits—to ensure documents from newer specification versions can pass through older library versions without data loss.

### [Lossless Round-Tripping](lossless-round-tripping.md)

What "lossless" means in this library, what is preserved during parse-serialize cycles, and why HTML conversion is explicitly not lossless.

### [Relationship to the Specification](relationship-to-specification.md)

The boundary between what the OpenSocial Rich Text JSON Exchange specification defines and what this PHP library implements. When to consult the spec versus the library documentation.

### [Security](security.md)

How the library protects against XSS and other injection attacks during HTML rendering, and what security concerns remain the responsibility of the consuming application.
