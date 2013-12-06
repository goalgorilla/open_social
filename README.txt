ABOUT

Module allows to print comments from social networks to pages of your website.
It provides three field formatters:
Google, Twitter and Facebook.

HOW TO USE

First, please, got to configuration page admin/config/system/social-comments.

Each field formatter provides URL field in which you need to paste link to the
post. After that on page where content for the selected field is displayed,
you will see comments being posted (those, that you pasted in URL field).
Just use it as any field in your content.

FEATURES

- caching result
- choose amount of comments to print
- use different templates for different view modes and content types
- integration with Views

TEMPLATE (THEME HOOK) SUGGESTIONS

social_items--[entity_type]
social_items--[bundle]
social_items--[type]
social_items--[type]--[bundle]

[entity_type] - name of entity type. E.g. node, taxonomy_term.
[bundle] - name of bundle. E.g. article, page, tags.
[type] - is a type of a field. E.g. google or facebook.

CREDITS

The idea and sponsorship by Drucode (http://drucode.com).
Mainteiner Max Petyurenko.
