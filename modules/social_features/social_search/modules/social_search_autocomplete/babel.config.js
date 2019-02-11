module.exports = function (api) {
  // Cache the configuration based on our environment.
  api.cache.using(() => process.env.NODE_ENV);

  return {
    "presets": [
      [
        "@babel/env",
        {
          // We try to support as many browsers as possible but exclude those
          // that are explicitly not supported by Drupal 8.
          // See: https://www.drupal.org/docs/8/system-requirements/browser-requirements
          "targets": "> 0.25%, not ie < 11, not firefox < 5, not opera < 12, not safari < 5, not dead"
        }
      ],
      [
        "@babel/preset-react",
        {
          "development": api.env("production")
        }
      ]
    ]
  };
};
