[![Build Status](https://api.travis-ci.org/Gizra/message.svg?branch=8.x-1.x)](https://travis-ci.org/Gizra/message)

Overview
========
A general logging utility that can be used as activity module.

* In message module, the arguments of a sentence can use tokens, custom
  calblacks or be hard-coded. Making the arguments dynamic means that the
  rendering time is slower than activity, on the other hand you can use
  callback functions to render the final output (see message_example module).
* Thanks to the dependency on the Entity API, the messages are exportable and
  integrated with the Features module.
* Message can use (but not as a dependency) the Rules module, to create message
  instances via the "Entity create" action, whereas the text replacement
  arguments can be set via the "Set data value" action.
* For displaying messages, the modules comes with Views support.

Tokens
======
* "Dynamic" tokens
  When defining a message type, it is possible to use Drupal tokens in any of the
  message fields, in order to inject certain content into the field on the fly.
  E.g. Entering the string "[current-date:short]" to the message text will
  display the current request time instead of the token.
  E.g. [message:user:mail] will be replaced with the message author's username
  (When displaying the message).
  If the message has fields (e.g field_node_ref), its contents will accessible
  by the token system as well under [message:field_node_ref].
  (For instance: [message:field_node_ref:title]).
  This relies on "Entity token" module that ships with Entity API.
  Enabling "Token" module is also recommened, as it provides more tokens
  and shows a token browser in the message type creation page.

* "Single use" tokens
  The single-use tokens are similiar to the dynamic tokens, excepet they're
  being replaced by their content as the message is created; Meaning this
  content will not get updated if it's reference gets changed.
  E.g. "@{message:user:name}" - Will be replaced by the message author's name
  (When creating the message).
  You can use this for example when you know the user's name is not going
  to change, so there is no reason for re-checking all the time the user
  name -- hardcoding it makes more sense.

* Custom message arguments (Custom callbacks)
  When creating a message, it's possible to store custom arguments that will be
  replaced when presenting the message.
  E.g. If the message was created with an argument called "@sometext", it will
  get inserted to the message text (On display time) whenever the string
  "@sometext" is encountered.
  This method also supports custom call-back functions with optional arguments
  stored on the message; In order to use a callback, create the message with
  an argument such as:
  '!replaced-by-foo' => [
    'callback' => 'foo',
    'callback arguments' => ['x', 'z']
  ]
  That will get the string '!replaced-by-foo' in the message body to be replaced
  by the output of calling foo('x', 'z').

Partials
========
The message body has multiple cardinallity, allowing to separate html markup
from the actual message content, and also, allowing to only render a selected
part of the message.
The partials are reflected in the "Manage display" page of every message type,
allowing the administrator to re-order and hide them per view mode.
Furthermore, if Views and Panels module are enabled, it is possible to render
the message "partials" using the views module's "Panel fields" format.
Enable the Message-example module to see it in action.

View modes
==========
Message module exposes the "message-text" field(s) and allows an
administrator to set visibility and order via "Manage display" page, e.g.
admin/structure/messages/manage/[YOUR-MESSAGE-TYPE]/display

Auto-purging
============
Message supports deleteion on Cron of messages according to quota and age
definition.

* Global purging definition
  Under admin/config/message it is possible to set purging definition by
  maximal quota or maximal message age in days.

* Message type purging definition
  Each message type my override the global purging settings. Under
  admin/structure/messages/manage/[YOUR-MESSAGE-TYPE], clicking the
  "Override global settings" checkbox will make the global settings ignore the
  current message type and will allow to set purging definitions for the current
  type.


