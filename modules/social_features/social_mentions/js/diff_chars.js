/**
 * Copyright (c) 2009-2011, Kevin Decker <kpdecker@gmail.com>
 */

(function () {

  "use strict";

  window.diffChars = function (oldString, newString) {
    var clonePath = function clonePath(path) {
      return {
        newPos: path.newPos,
        components: path.components.slice(0)
      };
    };

    var extractCommon = function extractCommon(basePath, newString, oldString, diagonalPath) {
      var newLen = newString.length,
        oldLen = oldString.length,
        newPos = basePath.newPos,
        oldPos = newPos - diagonalPath,
        commonCount = 0;

      while (newPos + 1 < newLen && oldPos + 1 < oldLen && newString[newPos + 1] == oldString[oldPos + 1]) {
        newPos++;
        oldPos++;
        commonCount++;
      }

      if (commonCount) {
        basePath.components.push({ count: commonCount });
      }

      basePath.newPos = newPos;

      return oldPos;
    };

    var pushComponent = function pushComponent(components, added, removed) {
      var last = components[components.length - 1];

      if (last && last.added === added && last.removed === removed) {
        // We need to clone here as the component clone operation is just
        // as shallow array clone.
        components[components.length - 1] = { count: last.count + 1, added: added, removed: removed };
      } else {
        components.push({ count: 1, added: added, removed: removed });
      }
    };

    var buildValues = function buildValues(components, newString, oldString) {
      var componentPos = 0,
        componentLen = components.length,
        newPos = 0,
        oldPos = 0;
      for (; componentPos < componentLen; componentPos++) {
        var component = components[componentPos];

        if (!component.removed) {
          component.value = newString.slice(newPos, newPos + component.count);
          newPos += component.count;
          // Common case.
          if (!component.added) {
            oldPos += component.count;
          }
        } else {
          component.value = oldString.slice(oldPos, oldPos + component.count);
          oldPos += component.count;
        }
      }

      return components;
    };

    // Handle the identity case (this is due to unrolling editLength == 0
    if (newString === oldString) {
      return [{ value: newString }];
    }

    if (!newString) {
      return [{ value: oldString, removed: true }];
    }

    if (!oldString) {
      return [{ value: newString, added: true }];
    }

    var newLen = newString.length,
      oldLen = oldString.length;
    var maxEditLength = newLen + oldLen;
    var bestPath = [{ newPos: -1, components: [] }];
    // Seed editLength = 0, i.e. the content starts with the same values
    var oldPos = extractCommon(bestPath[0], newString, oldString, 0);

    if (bestPath[0].newPos + 1 >= newLen && oldPos + 1 >= oldLen) {
      // Identity per the equality and tokenizer
      return [{ value: newString }];
    }

    // Main worker method. checks all permutations of a given edit length for acceptance.
    var execEditLength = function execEditLength() {
      for (var diagonalPath = -1 * editLength; diagonalPath <= editLength; diagonalPath += 2) {
        var basePath = void 0;
        var addPath = bestPath[diagonalPath - 1],
          removePath = bestPath[diagonalPath + 1];
        _oldPos = (removePath ? removePath.newPos : 0) - diagonalPath;
        if (addPath) {
          // No one else is going to attempt to use this value, clear it
          bestPath[diagonalPath - 1] = undefined;
        }
        var canAdd = addPath && addPath.newPos + 1 < newLen;
        var canRemove = removePath && 0 <= _oldPos && _oldPos < oldLen;
        if (!canAdd && !canRemove) {
          // If this path is a terminal then prune
          bestPath[diagonalPath] = undefined;
          continue;
        }
        // Select the diagonal that we want to branch from. We select the prior
        // path whose position in the new string is the farthest from the origin
        // and does not pass the bounds of the diff graph
        if (!canAdd || canRemove && addPath.newPos < removePath.newPos) {
          basePath = clonePath(removePath);
          pushComponent(basePath.components, undefined, true);
        } else {
          basePath = addPath; // No need to clone, we've pulled it from the list
          basePath.newPos++;
          pushComponent(basePath.components, true, undefined);
        }

        var _oldPos = extractCommon(basePath, newString, oldString, diagonalPath);
        // If we have hit the end of both strings, then we are done
        if (basePath.newPos + 1 >= newLen && _oldPos + 1 >= oldLen) {
          return buildValues(basePath.components, newString, oldString);
        } else {
          // Otherwise track this path as a potential candidate and continue.
          bestPath[diagonalPath] = basePath;
        }
      }
      editLength++;
    };

    // Performs the length of edit iteration. Is a bit fugly as this has to support the
    // sync and async mode which is never fun. Loops over execEditLength until a value
    // is produced.
    var editLength = 1;

    while (editLength <= maxEditLength) {
      var ret = execEditLength();

      if (ret) {
        return ret;
      }
    }
  };


})();
