/* 🕹️ NARRRFS_MOBILE_ARCADE_V1
 * Shared scroll-lock, viewport framing, slide-capable D-pad, and hold-button
 * helpers across all 6 website arcade games. Pure input ergonomics & layout.
 */
(() => {
  'use strict';

  let isLocked = false;
  let activeGameKey = '';
  let lockedScrollX = 0;
  let lockedScrollY = 0;
  let listenersAttached = false;

  function isTouchOrCompactViewport() {
    return (
      window.innerWidth <= 1024 ||
      'ontouchstart' in window ||
      (typeof navigator !== 'undefined' && navigator.maxTouchPoints > 0)
    );
  }

  function handleWindowTouchMove(event) {
    if (!isLocked) return;
    const target = event.target;
    if (target && typeof target.closest === 'function' && target.closest('[data-allow-arcade-scroll="true"]')) {
      return;
    }
    if (event.cancelable) {
      event.preventDefault();
    }
  }

  function handleWindowScroll() {
    if (!isLocked) return;
    const dx = Math.abs(window.scrollX - lockedScrollX);
    const dy = Math.abs(window.scrollY - lockedScrollY);
    if (dx > 2 || dy > 2) {
      window.scrollTo(lockedScrollX, lockedScrollY);
    }
  }

  function attachGlobalGuards() {
    if (listenersAttached) return;
    window.addEventListener('touchmove', handleWindowTouchMove, { capture: true, passive: false });
    window.addEventListener('scroll', handleWindowScroll, { passive: true });
    listenersAttached = true;
  }

  function detachGlobalGuards() {
    if (!listenersAttached) return;
    window.removeEventListener('touchmove', handleWindowTouchMove, { capture: true });
    window.removeEventListener('scroll', handleWindowScroll);
    listenersAttached = false;
  }

  function frameStageForMobile(stageEl) {
    if (!stageEl || typeof stageEl.getBoundingClientRect !== 'function') return;
    if (!isTouchOrCompactViewport()) return;

    const headerEl = document.querySelector('header');
    let headerOffset = 4;
    if (headerEl) {
      const headerStyle = window.getComputedStyle(headerEl);
      if (headerStyle.position === 'sticky' || headerStyle.position === 'fixed') {
        headerOffset = Math.min(84, Math.ceil(headerEl.getBoundingClientRect().height) + 4);
      }
    }

    const rect = stageEl.getBoundingClientRect();
    const docTop = rect.top + (window.scrollY || window.pageYOffset || 0);
    const vh = (window.visualViewport && window.visualViewport.height) || window.innerHeight || 0;
    let targetY = Math.max(0, docTop - headerOffset);

    if (rect.height > 0 && vh > 0 && rect.height + headerOffset < vh) {
      const freeSpace = vh - headerOffset - rect.height;
      targetY = Math.max(0, docTop - headerOffset - Math.min(20, Math.floor(freeSpace / 2)));
    }

    window.scrollTo({ top: targetY, left: 0, behavior: 'instant' });
  }

  function dispatchLockState(locked, gameKey) {
    try {
      window.dispatchEvent(new CustomEvent('narrrfs:arcade-lock-change', {
        detail: { locked, gameKey }
      }));
    } catch (_) {
      // Ignore Event errors on legacy WebViews.
    }
  }

  function lock(options = {}) {
    const stageEl = options.stageEl || null;
    const gameKey = String(options.gameKey || 'arcade');

    if (!isLocked) {
      frameStageForMobile(stageEl);
    }

    lockedScrollX = window.scrollX || window.pageXOffset || 0;
    lockedScrollY = window.scrollY || window.pageYOffset || 0;
    isLocked = true;
    activeGameKey = gameKey;

    document.documentElement.classList.add('narrrfs-game-locked');
    if (document.body) {
      document.body.classList.add('narrrfs-game-locked');
      document.body.dataset.activeArcadeGame = gameKey;
    }

    attachGlobalGuards();
    dispatchLockState(true, gameKey);
  }

  function unlock() {
    const previousKey = activeGameKey;
    isLocked = false;
    activeGameKey = '';

    detachGlobalGuards();
    document.documentElement.classList.remove('narrrfs-game-locked');
    if (document.body) {
      document.body.classList.remove('narrrfs-game-locked');
      delete document.body.dataset.activeArcadeGame;
    }

    dispatchLockState(false, previousKey);
  }

  /**
   * Bind tap + thumb-slide directional controls across a D-pad container.
   * Players can slide directly from Left -> Down -> Right without lifting.
   * Supports isolated multi-touch (left thumb on D-pad while right thumb fires)
   * and an optional `onRelease` callback when sliding off or lifting.
   */
  function bindSlideDpad(containerEl, onDirection, dirAttr = 'data-arcade-dir', options = {}) {
    if (!containerEl || typeof onDirection !== 'function') return () => {};

    const resolvedOptions = typeof options === 'function' ? { onRelease: options } : (options || {});
    const onRelease = typeof resolvedOptions.onRelease === 'function' ? resolvedOptions.onRelease : null;

    let pointerActive = false;
    let activeDir = null;
    let activeBtn = null;
    let activePointerId = null;
    let activeTouchId = null;
    let lastTouchTimestamp = 0;

    function clearVisuals() {
      const wasActive = pointerActive || activeDir !== null || activeBtn !== null;
      const prevDir = activeDir;
      if (activeBtn) {
        activeBtn.classList.remove('is-pressed');
        activeBtn = null;
      }
      containerEl.querySelectorAll('.is-pressed').forEach((el) => el.classList.remove('is-pressed'));
      activeDir = null;
      pointerActive = false;
      activePointerId = null;
      activeTouchId = null;
      if (wasActive && onRelease) {
        onRelease(prevDir);
      }
    }

    function activateFromPoint(clientX, clientY) {
      const hit = document.elementFromPoint(clientX, clientY);
      const btn = hit && typeof hit.closest === 'function' ? hit.closest(`[${dirAttr}]`) : null;
      if (!btn || !containerEl.contains(btn)) {
        const prevDir = activeDir;
        if (activeBtn) {
          activeBtn.classList.remove('is-pressed');
          activeBtn = null;
        }
        activeDir = null;
        if (prevDir !== null && onRelease) {
          onRelease(prevDir);
        }
        return;
      }
      const dir = btn.getAttribute(dirAttr);
      if (!dir) return;
      if (dir !== activeDir || btn !== activeBtn) {
        if (activeBtn && activeBtn !== btn) {
          activeBtn.classList.remove('is-pressed');
        }
        activeBtn = btn;
        activeDir = dir;
        btn.classList.add('is-pressed');
        onDirection(dir);
      }
    }

    function onPointerDown(e) {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      if (pointerActive && activePointerId !== null && e.pointerId !== activePointerId) return;
      lastTouchTimestamp = Date.now();
      e.preventDefault();
      e.stopPropagation();
      pointerActive = true;
      activePointerId = e.pointerId;
      try {
        containerEl.setPointerCapture?.(e.pointerId);
      } catch (_) {}
      activateFromPoint(e.clientX, e.clientY);
    }

    function onPointerMove(e) {
      if (!pointerActive) return;
      if (activePointerId !== null && e.pointerId !== activePointerId) return;
      e.preventDefault();
      e.stopPropagation();
      activateFromPoint(e.clientX, e.clientY);
    }

    function onPointerUpOrCancel(e) {
      if (!pointerActive) return;
      if (activePointerId !== null && e.pointerId !== activePointerId) return;
      e.preventDefault();
      e.stopPropagation();
      try {
        containerEl.releasePointerCapture?.(e.pointerId);
      } catch (_) {}
      clearVisuals();
    }

    function findTrackedTouch(touchList) {
      if (!touchList || !touchList.length) return null;
      if (activeTouchId === null) return touchList[0];
      for (let i = 0; i < touchList.length; i++) {
        if (touchList[i].identifier === activeTouchId) {
          return touchList[i];
        }
      }
      return null;
    }

    function onTouchStart(e) {
      // Fallback for WebViews with partial PointerEvent capture behavior
      lastTouchTimestamp = Date.now();
      e.preventDefault();
      e.stopPropagation();
      if (pointerActive) return;
      const touch = (e.changedTouches && e.changedTouches[0]) || (e.touches && e.touches[0]);
      if (!touch) return;
      pointerActive = true;
      activeTouchId = touch.identifier;
      activateFromPoint(touch.clientX, touch.clientY);
    }

    function onTouchMove(e) {
      if (!pointerActive) return;
      const touch = findTrackedTouch(e.touches);
      if (!touch) return;
      e.preventDefault();
      e.stopPropagation();
      activateFromPoint(touch.clientX, touch.clientY);
    }

    function onTouchEnd(e) {
      if (!pointerActive) return;
      const endedTracked = activeTouchId === null || Boolean(findTrackedTouch(e.changedTouches));
      if (!endedTracked) return;
      e.preventDefault();
      e.stopPropagation();
      clearVisuals();
    }

    function onClickFallback(e) {
      // Keep keyboard/mouse click accessible when touch didn't just trigger it
      if (Date.now() - lastTouchTimestamp < 450) {
        e.preventDefault();
        return;
      }
      const btn = e.target && typeof e.target.closest === 'function' ? e.target.closest(`[${dirAttr}]`) : null;
      if (!btn || !containerEl.contains(btn)) return;
      const dir = btn.getAttribute(dirAttr);
      if (dir) {
        onDirection(dir);
        if (onRelease) onRelease(dir);
      }
    }

    const supportsPointer = typeof window.PointerEvent === 'function';
    if (supportsPointer) {
      containerEl.addEventListener('pointerdown', onPointerDown, { passive: false });
      containerEl.addEventListener('pointermove', onPointerMove, { passive: false });
      containerEl.addEventListener('pointerup', onPointerUpOrCancel, { passive: false });
      containerEl.addEventListener('pointercancel', onPointerUpOrCancel, { passive: false });
    } else {
      containerEl.addEventListener('touchstart', onTouchStart, { passive: false });
      containerEl.addEventListener('touchmove', onTouchMove, { passive: false });
      containerEl.addEventListener('touchend', onTouchEnd, { passive: false });
      containerEl.addEventListener('touchcancel', onTouchEnd, { passive: false });
    }
    containerEl.addEventListener('click', onClickFallback);
    containerEl.addEventListener('contextmenu', (e) => e.preventDefault());
    document.addEventListener('visibilitychange', () => {
      if (document.hidden && pointerActive) clearVisuals();
    });

    return clearVisuals;
  }

  /**
   * Bind a repeatable press-and-hold arcade action button (Tetris move/drop, Invaders move/fire).
   */
  function bindHoldButton(btnEl, onStep, options = {}) {
    if (!btnEl || typeof onStep !== 'function') return () => {};
    const initialDelayMs = Number(options.initialDelayMs ?? 190);
    const repeatMs = Number(options.repeatMs ?? 85);
    const onRelease = typeof options.onRelease === 'function' ? options.onRelease : null;

    let holding = false;
    let delayTimer = null;
    let repeatTimer = null;
    let lastTouchTimestamp = 0;

    function stopHold() {
      if (delayTimer) {
        clearTimeout(delayTimer);
        delayTimer = null;
      }
      if (repeatTimer) {
        clearInterval(repeatTimer);
        repeatTimer = null;
      }
      if (holding) {
        holding = false;
        btnEl.classList.remove('is-pressed');
        if (onRelease) onRelease();
      }
    }

    function startHold() {
      stopHold();
      holding = true;
      btnEl.classList.add('is-pressed');
      onStep();
      if (repeatMs > 0) {
        delayTimer = setTimeout(() => {
          if (!holding) return;
          repeatTimer = setInterval(() => {
            if (holding) onStep();
          }, repeatMs);
        }, initialDelayMs);
      }
    }

    const supportsPointer = typeof window.PointerEvent === 'function';
    if (supportsPointer) {
      btnEl.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        if (e.pointerType === 'touch') lastTouchTimestamp = Date.now();
        e.preventDefault();
        e.stopPropagation();
        try {
          btnEl.setPointerCapture?.(e.pointerId);
        } catch (_) {}
        startHold();
      }, { passive: false });
      btnEl.addEventListener('pointerup', (e) => {
        e.preventDefault();
        stopHold();
      }, { passive: false });
      btnEl.addEventListener('pointercancel', () => stopHold(), { passive: false });
    } else {
      btnEl.addEventListener('touchstart', (e) => {
        lastTouchTimestamp = Date.now();
        e.preventDefault();
        e.stopPropagation();
        startHold();
      }, { passive: false });
      btnEl.addEventListener('touchend', (e) => {
        e.preventDefault();
        stopHold();
      }, { passive: false });
      btnEl.addEventListener('touchcancel', () => stopHold(), { passive: false });
    }

    btnEl.addEventListener('click', (e) => {
      if (Date.now() - lastTouchTimestamp < 450) {
        e.preventDefault();
        return;
      }
      e.preventDefault();
      if (!supportsPointer) {
        onStep();
        if (onRelease) onRelease();
      }
    });
    btnEl.addEventListener('contextmenu', (e) => e.preventDefault());
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stopHold();
    });

    return stopHold;
  }

  window.addEventListener('pagehide', unlock);
  window.addEventListener('beforeunload', unlock);

  window.NarrrfsMobileArcade = {
    lock,
    unlock,
    isLocked: () => isLocked,
    bindSlideDpad,
    bindHoldButton
  };
})();
