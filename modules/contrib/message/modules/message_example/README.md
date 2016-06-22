Message example module is intended for developers and site builders
wanting to understand the key concepts and features of Message module.

1) Enable all dependencies
2) Add a few users (drush genu 5)
3) Add nodes and comments (drush genc 10 5)
4) Navigate to message-example
5) Unpublish nodes and comments to see how the message is unpublished
   as-well

Developers should read the message.module file as it holds many code
comments.
The UAS (User activity stream) view is dependent on Panels, as it uses
"Panel fields" as a row plugin, along with the Message's partials.
