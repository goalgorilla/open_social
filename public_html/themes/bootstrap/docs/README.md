<!-- @file Documentation landing page and topics for the http://drupal-bootstrap.org site. -->
<!-- @mainpage -->
# Drupal Bootstrap Documentation

{.lead} The official documentation site for the [Drupal Bootstrap] base theme

The majority of this site is automatically generated from source files
located through out the project's repository. Topics are extracted from Markdown
files and the rest is extracted from embedded PHP comments.

---

## Topics

Below are some topics to help get you started using the [Drupal Bootstrap] base
theme. They are ordered based on the level one typically progresses while using
a base theme like this.

#### @link faq FAQ @endlink

#### @link getting_started Getting Started @endlink

#### @link subtheme Sub-Theming @endlink
- @link subtheme_settings Theme Settings @endlink
- @link subtheme_cdn CDN Starterkit @endlink
- @link subtheme_less LESS Starterkit @endlink

#### @link registry Theme Registry @endlink
- @link theme_preprocess Preprocess@endlink
- @link templates Templates@endlink

#### @link api APIs @endlink

#### @link contribute Contribute @endlink
- @link contribute_maintainers Project Maintainers @endlink

---

## Terminology

The term **"bootstrap"** can be used excessively through out this project's
documentation. For clarity, we will always attempt to use this word verbosely
in one of the following ways:

- **[Drupal Bootstrap]** refers to the Drupal base theme project.
- **[Bootstrap Framework](http://getbootstrap.com)** refers to the external
  front end framework.
- **[drupal_bootstrap](https://api.drupal.org/apis/drupal_bootstrap)** refers
  to Drupal's bootstrapping process or phase.
  
When referring to files inside the [Drupal Bootstrap] project directory, they
will always start with `./bootstrap` and continue to specify the full path to
the file or directory inside it. For example, the file that is responsible for
displaying the text on this page is located at `./bootstrap/docs/README.md`.

When referring to files inside a sub-theme, they will always start with
`./subtheme/` and continue to specify the full path to the file or directory
inside it. For example, the primary file Drupal uses to determine if a theme
exists is: `./subtheme/subtheme.info.yml`, where `subtheme` is the machine name
of your sub-theme.

[Drupal Bootstrap]: https://www.drupal.org/project/bootstrap
