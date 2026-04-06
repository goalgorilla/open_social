# Signed File Upload

Implements the ability to handle file uploads using signed URLs according to
the [tus protocol for resumable uploads](https://tus.io/protocols/resumable-upload).

Supports the Core Protocol and the following Protocol Extensions:

- Expiration
- Termination

The module is aimed at developers and upload creation and authentication
should be handled by integrators of this module. This module provides
integrators with a signed upload URL that can be used to securely upload
files regardless of the original authentication method.

For integrators the module supports the defer-length part of creation.

## Security

The module does not utilise Drupal's image validation since it does not work
with the resumable upload model. Instead it provides security in a few
different places.

Upload constraints are resolved from Drupal upload destinations (entity fields
and editors). These constraints are validated whenever possible. This means
filenames are validated for validity and type before the upload begins. Byte
based heuristics are applied during file upload for supported types. During
finalization the length, name and mime type are checked. For any special
file fields (e.g. images) their additional constraints (e.g. dimensions) are
also validated.
