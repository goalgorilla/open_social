# Rich Text JSON Exchange Library — AI Agent Guide

This document defines **hard constraints, project intent, and working rules** for AI agents contributing to the Rich Text JSON Exchange PHP library.

This is not a marketing document. It is an **operational contract** between the agent and the codebase.

---

## Purpose

This guide ensures that any change made by an AI agent:

- Preserves the library’s **core guarantees**
- Respects the **specification-first nature** of the project
- Keeps **code, tests, and documentation in sync**
- Avoids architectural drift and accidental scope creep

If you are unsure whether a change violates these principles, **do not implement it**. Propose it instead.

---

## Project Overview

This library implements the **OpenSocial Rich Text JSON Exchange format** in PHP 8.3+.

### What This Library Does

- Parses JSON into strictly-typed PHP DTOs
- Validates document structure against format rules
- Preserves unknown nodes, fields, and bitmask bits (**forward compatibility is a hard requirement**)
- Provides immutable editing APIs
- Serializes back to JSON with **lossless round-trip guarantees**
- Renders documents to safe, escaped HTML
- Imports HTML into documents (best-effort conversion)

### What This Library Does NOT Do

- Provide a WYSIWYG editor or UI components
- Replace or reinterpret the specification
- Silently drop unknown data
- “Fix” invalid documents by guessing intent
- Sanitize untrusted HTML (must be done before importing)
- Define storage, persistence, or transport mechanisms

### Canonical Specification (Non-Negotiable)

The normative specification is:

**Open-Social-Rich-Text-JSON-exchange-format.pdf**

- This is the **only authority** on the format
- If the code and the spec disagree, the **code is wrong**
- Any spec deviation must be:
    - Intentional
    - Documented
    - Explicitly justified

---

## Core Invariants (Must Never Be Broken)

All changes must preserve these properties:

1. **Lossless round-trip**: Parsing → serializing must not lose information
2. **Forward compatibility**: Unknown nodes, fields, and bits must be preserved
3. **Immutability**: Public DTOs and documents must not be mutable
4. **Spec alignment**: Validation rules and structures must follow the spec
5. **No silent data loss**: Ever.

If a change threatens any of these, stop and reconsider.

---

## Understanding Existing Design Decisions

Before changing anything:

- **Read the spec**: `Open-Social-Rich-Text-JSON-exchange-format.pdf`
- **Read explanation docs**: `docs/explanation/`
- **Check reference docs**: `docs/reference/`
- **Study existing code**: `src/`

The goal is to understand **why** things look the way they do. Do not “simplify” away constraints that exist for format or compatibility reasons.

---

## Change Classification (Think Before You Code)

Every change falls into one of these categories:

- **Refactor only**: No behavior change, no API change
- **Bug fix**: Behavior changes, but to better match spec or docs
- **Feature addition**: New capability, no breaking change
- **API change**: Public surface changes
- **Spec-aligned change**: Required because of the specification

If it is anything other than **refactor only**, you must:

- Add or update tests
- Update documentation
- Re-check spec alignment

If it is an **API change or spec-impacting change**, prefer proposing before implementing.

---

## Code Development Practices

### Language and Compatibility

- PHP 8.3+ required
- `declare(strict_types=1);` in every PHP file
- Namespace: `OpenSocial\RichTextJson`

### Code Quality Standards

All code must pass:

- **PHPStan** (configured in repo)
- **PHPCS** (Drupal coding standards)

Commands:

```bash
vendor/bin/phpstan analyse
vendor/bin/phpcs
```

### Development Approach

- **Test-driven**: Tests define behavior first
- **Explicit over clever**
- **Correct over convenient**
- **Security always**:
    - Escape output
    - Sanitize URLs
    - Never emit raw user-controlled HTML
- Use PHPDoc to clarify intent and help PHPStan

### Dependency Management

When adding Composer dependencies:

1. Check if something in `applications/community_management_system/composer.json` already covers the need
2. If not, explicitly justify:
    - Why it is needed
    - Where it is used
    - Why existing dependencies are insufficient
3. Do not add unapproved dependencies.

---

## Testing Practices

### Test-First Workflow

1. Write tests that define the behavior
2. Confirm they fail (or capture a bug)
3. Implement the change
4. Run the full test and quality suite

### Commands

```bash
vendor/bin/phpunit
vendor/bin/phpunit tests/src/path/to/TestFile.php
vendor/bin/phpunit --coverage-html coverage/
```

### Quality Gate

```bash
vendor/bin/phpstan analyse
vendor/bin/phpcs
vendor/bin/phpunit
```

All must pass.

### Test Organization

- Tests mirror `src/` in `tests/src/`
- Test classes end with `Test`
- Test names must describe observable behavior

---

## Documentation Practices

### Diátaxis Framework

All documentation follows **Diátaxis**:

- `docs/tutorials/` — Learning-oriented
- `docs/how-to/` — Task-oriented
- `docs/reference/` — API contracts
- `docs/explanation/` — Design rationale

### Documentation Rules

- Document **PHP API usage**, not the spec itself
- Assume the reader knows the spec exists
- Examples must be realistic and conservative
- Never lie or hand-wave behavior

---

## CRITICAL RULE: Code and Docs Must Move Together

Any change that affects behavior, API, or guarantees **must** update:

1. `README.md`
2. `docs/tutorials/`
3. `docs/how-to/`
4. `docs/reference/`
5. `docs/explanation/` (if rationale changes)

### Documentation Updates Are Mandatory When:

- Public APIs change
- Behavior changes
- New features are added
- Something is deprecated or removed
- Error handling or validation changes

---

## Definition of Done (Agent Checklist)

Before considering work complete:

- [ ] Tests added or updated
- [ ] All tests pass
- [ ] PHPStan passes
- [ ] PHPCS passes
- [ ] Docs updated (if applicable)
- [ ] Core invariants still hold
- [ ] No silent data loss introduced

---

## Project Structure

```
libraries/rich_text_json_exchange/
├── src/                          # Source code (OpenSocial\RichTextJson namespace)
│   ├── Bitmask/                  # Format and detail bitmask handling
│   ├── Document/                 # Document and root node
│   ├── Exception/                # Typed exceptions
│   ├── Node/                     # Node DTOs (block, inline, unknown)
│   ├── Renderer/                 # HTML rendering
│   ├── Validation/               # Document validation
│   └── Visitor/                  # Tree traversal and transformation
├── tests/src/                    # Test files (mirrors src/ structure)
├── docs/                         # Documentation
│   ├── tutorials/                # Learning-oriented guides
│   ├── how-to/                   # Task-oriented recipes
│   ├── reference/                # API documentation
│   └── explanation/              # Conceptual background
├── vendor/                       # Composer dependencies
├── composer.json                 # Dependency configuration
├── phpunit.xml                   # PHPUnit configuration
├── phpcs.xml                     # PHPCS configuration
├── phpstan.neon                  # PHPStan configuration
├── Open-Social-Rich-Text-JSON-exchange-format.pdf  # Canonical specification
└── README.md                     # Project introduction
```

---

## Quick Reference

### Key Files

- Spec: `Open-Social-Rich-Text-JSON-exchange-format.pdf`
- README: `README.md`
- Entry point: `src/Document/RichTextDocument.php`

### Common Commands

```bash
composer install
vendor/bin/phpstan analyse && vendor/bin/phpcs && vendor/bin/phpunit
vendor/bin/phpcbf
```

---

## Working Protocol for AI Agents

1. Understand the request
2. Locate relevant code and tests
3. Check docs and spec
4. Decide the change category
5. Write tests
6. Implement
7. Update docs
8. Run full quality gate
9. Re-check invariants

---

## Final Reminder

This library is a **format implementation**, not an app.

Correctness, fidelity, and forward compatibility matter more than convenience, shortcuts, or cleverness.
