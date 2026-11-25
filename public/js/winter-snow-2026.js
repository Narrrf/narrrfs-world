/* ❄️ NARRRFS_WINTER_SNOW_2026
 * Presentation-only, ultra-subtle snowfall overlay for Winter 2026 pages.
 * Never blocks pointer events, never touches state, forms, or gameplay.
 */
(() => {
  'use strict';

  function initWinterSnow() {
    if (!document.body || !document.body.classList.contains('winter-theme')) return;
    if (document.querySelector('.winter-snowfield')) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const isCompactViewport = window.innerWidth < 768;
    const count = isCompactViewport ? 8 : 14;
    const snowfield = document.createElement('div');
    snowfield.className = 'winter-snowfield';
    snowfield.setAttribute('aria-hidden', 'true');

    const frag = document.createDocumentFragment();
    const minLeft = 4;
    const spanLeft = 92;
    const slotWidth = spanLeft / count;

    for (let i = 0; i < count; i += 1) {
      const flake = document.createElement('span');
      const isGlyph = (i + 1) % 4 === 0;
      const offsetRatio = ((i * 37) % 11) / 10; // deterministic 0.0..1.0 spread
      const leftPct = minLeft + i * slotWidth + offsetRatio * (slotWidth * 0.65);
      const durationSec = 25 + ((i * 7) % 16); // 25s..40s gentle fall
      const delaySec = -(2 + ((i * 11) % 35)); // -2s..-36s distributed start
      const swayPx = (i % 2 === 0 ? 1 : -1) * (8 + ((i * 5) % 11)); // +/-8px..18px

      flake.className = isGlyph ? 'winter-flake winter-flake--glyph' : 'winter-flake winter-flake--dot';
      flake.style.left = leftPct.toFixed(2) + '%';
      flake.style.setProperty('--flake-duration', durationSec + 's');
      flake.style.setProperty('--flake-delay', delaySec + 's');
      flake.style.setProperty('--flake-sway', swayPx + 'px');

      if (isGlyph) {
        const glyphSize = 10 + (i % 3); // 10px..12px
        const glyphOpacity = 0.22 + ((i * 3) % 13) * 0.01; // 0.22..0.34
        flake.textContent = '❄';
        flake.style.fontSize = glyphSize + 'px';
        flake.style.opacity = glyphOpacity.toFixed(2);
      } else {
        const dotSize = 3.5 + (i % 3); // 3.5px..5.5px
        const dotOpacity = 0.20 + ((i * 3) % 17) * 0.01; // 0.20..0.36
        flake.style.width = dotSize + 'px';
        flake.style.height = dotSize + 'px';
        flake.style.opacity = dotOpacity.toFixed(2);
      }

      frag.appendChild(flake);
    }

    snowfield.appendChild(frag);
    document.body.appendChild(snowfield);

    const syncVisibility = () => {
      if (document.body) {
        document.body.classList.toggle('winter-tab-hidden', Boolean(document.hidden));
      }
    };
    document.addEventListener('visibilitychange', syncVisibility, { passive: true });
    syncVisibility();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWinterSnow, { once: true });
  } else {
    initWinterSnow();
  }
})();
