core: 8.x
api: 2

# Include the definition for how to build Drupal core directly, including patches:
includes:
  - drupal-org-core.make
  - drupal-org.make

# Download the Social install profile and recursively build all its dependencies:
projects:
  social:
    type: profile
    download:
      type: git
      branch: '8.x-1.x'
