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
social_items--[view_mode]
social_items--[bundle]
social_items--[bundle]--[view_mode]
social_items--[entity_type]--[view_mode]
social_items--[entity_type]--[bundle]--[view_mode]
social_items--[type]
social_items--[type]--[bundle]
social_items--[type]--[view_mode]
social_items--[type]--[bundle]--[view_mode]
social_items--[type]--[entity_type]--[view_mode]
social_items--[type]--[entity_type]--[bundle]--[view_mode]

[entity_type] - name of entity type. E.g. node, taxonomy_term.
[bundle] - name of bundle. E.g. article, page, tags.
[type] - is a type of a field. E.g. google or facebook.
[view_mode] - name of view mode. E.g. full, teaser, token.

CREDITS

The idea and sponsorship by Drucode (http://drucode.com).
Developer - Max Petyurenko.
