/**
 * @file
 * Provides context that can be used to pass around translation functions.
 *
 * This allows the components to be decoupled from Drupal while still using the
 * Drupal.t and Drupal.formatPlural functions.
 */
import React from "react";

// By default the functions just passthrough the value that they receive.
const TranslationContext = React.createContext({ t: s => s, formatPlural: s => s });

export default TranslationContext;
