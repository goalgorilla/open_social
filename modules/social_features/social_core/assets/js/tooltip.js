/**
 * @file
 * Tooltip behavior for Open Social.
 *
 * Features:
 * - Automatically attaches lightweight tooltips to any element with the attribute:
 *     [data-social-tooltip="Your tooltip text"]
 * - Allows a preferred tooltip position using:
 *     [data-social-tooltip-position="top"|"bottom"|"left"|"right"]
 * - Defaults to the 'right' position if none specified.
 * - Tooltip automatically repositions itself if it would be off-screen.
 * - Updates position continuously while the tooltip is visible (handles scroll/resize).
 * - RTL-aware: automatically flips left/right positioning in RTL languages.
 *
 * Usage:
 * - Add `data-social-tooltip="Tooltip content"` to any HTML element.
 * - Optional: Add `data-social-tooltip-position="top"` to control default position.
 *
 * Example:
 *   <span data-social-tooltip="Hello!" data-social-tooltip-position="top">Hover me</span>
 */

(function (Drupal, $, once, debounce) {
  'use strict';

  const OFFSET = 8;
  let currentTooltip = null;
  let currentElement = null;

  function createTooltipElement(text) {
    const tooltip = document.createElement("div");
    tooltip.className = "social-tooltip";
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    return tooltip;
  }

  function getPositionData(target, tooltip, position) {
    const rect = target.getBoundingClientRect();
    const tipRect = tooltip.getBoundingClientRect();
    let top = 0, left = 0;

    switch (position) {
      case 'top':
        top = rect.top - tipRect.height - OFFSET;
        left = rect.left + (rect.width - tipRect.width) / 2;
        break;
      case 'bottom':
        top = rect.bottom + OFFSET;
        left = rect.left + (rect.width - tipRect.width) / 2;
        break;
      case 'left':
        top = rect.top + (rect.height - tipRect.height) / 2;
        left = rect.left - tipRect.width - OFFSET;
        break;
      case 'right':
        top = rect.top + (rect.height - tipRect.height) / 2;
        left = rect.right + OFFSET;
        break;
    }

    return { top, left };
  }

  function isInViewport(top, left, tooltip) {
    const width = tooltip.offsetWidth;
    const height = tooltip.offsetHeight;
    return (
      top >= 0 &&
      left >= 0 &&
      (top + height) <= window.innerHeight &&
      (left + width) <= window.innerWidth
    );
  }

  function positionTooltip(target, tooltip, preferred) {
    const dir = document.documentElement.dir;
    let positions = [preferred, 'top', 'bottom', 'right', 'left'];

    if (dir === 'rtl') {
      positions = positions.map(pos => {
        if (pos === 'left') return 'right';
        if (pos === 'right') return 'left';
        return pos;
      });
    }

    positions = positions.filter((pos, i, self) => pos && self.indexOf(pos) === i);

    for (const pos of positions) {
      const { top, left } = getPositionData(target, tooltip, pos);
      tooltip.style.top = `${top}px`;
      tooltip.style.left = `${left}px`;
      if (isInViewport(top, left, tooltip)) {
        return;
      }
    }

    tooltip.style.top = `${(window.innerHeight - tooltip.offsetHeight) / 2}px`;
    tooltip.style.left = `${(window.innerWidth - tooltip.offsetWidth) / 2}px`;
  }

  const debouncedReposition = debounce(() => {
    if (currentTooltip && currentElement) {
      const preferred = currentElement.getAttribute("data-social-tooltip-position") || 'right';
      positionTooltip(currentElement, currentTooltip, preferred);
    }
  }, 100);

  Drupal.behaviors.socialTooltip = {
    attach(context) {
      once('social-tooltip-init', '[data-social-tooltip]', context).forEach((el) => {
        const text = el.getAttribute("data-social-tooltip");
        const preferred = el.getAttribute("data-social-tooltip-position") || 'right';

        el.addEventListener("mouseenter", () => {
          currentTooltip = createTooltipElement(text);
          currentElement = el;
          positionTooltip(el, currentTooltip, preferred);
        });

        el.addEventListener("mouseleave", () => {
          if (currentTooltip) {
            currentTooltip.remove();
            currentTooltip = null;
            currentElement = null;
          }
        });
      });

      window.addEventListener("resize", debouncedReposition);
      window.addEventListener("scroll", debouncedReposition);
    }
  };

})(Drupal, jQuery, once, Drupal.debounce);
