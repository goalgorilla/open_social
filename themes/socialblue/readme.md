# Social Blue
Social blue is made to provide as a demo as well as a default style for Open Social. This is a sub theme of socialbase.

# What can I do with this theme?
The safest and fastest way to get started is to duplicate this theme and rename it to a custom name. You need to make sure all instances of 'socialblue' are renamed to guarantee a proper working theme. Socialblue itself will be updated in the future with new features, so it is best not to make changes. You might lose it when updating.

If you want to utilise the gulp plugins we have provided you need to install the plugins again, via yarn install (which will read the package.json file).

As you can see in the info file, we are mostly extending the socialbase libraries with the socialblue variant. This means there is a relation between the two and because we load some libraries via twig files conditionally this ensure we are not forgetting to load the 'styling' layer for a component.

You can override template files just like in any other theme. Just create a `templates` folder and put you new template files there.

Any questions or feedback?
[Create an issue on drupal.org](https://www.drupal.org/project/issues/social)
