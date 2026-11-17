/* 🎄 Christmas Advent 2026 V10 — NON-MUTATING locked-door preview.
 * DEVS FOR DECADES: doors open only informational holiday dialogs; NO claim is attempted.
 * Future backend must determine official Vienna day, eligibility, rewards, and claim state.
 * Never mark a door claimed, alter progress, or issue rewards based on a browser click.
 * Do not add fetch/POST, localStorage payout markers, or client-side unlocking
 * until a separately approved and security-reviewed server contract exists.
 */
(() => {
  'use strict';
  const calendar = document.getElementById('christmas-advent-2026');
  if (!calendar) return;

  const doors = calendar.querySelector('#christmas26-door-grid');
  const countdown = calendar.querySelector('#christmas26-countdown');
  if (!doors || !countdown) return;

  const modal = document.getElementById('christmas26-modal');
  const modalDay = document.getElementById('christmas26-modal-day');
  const modalDescription = document.getElementById('christmas26-modal-description');
  const modalClose = document.getElementById('christmas26-modal-close');
  const modalReturn = document.getElementById('christmas26-modal-return');
  const canShowPreview = Boolean(modal && modalDay && modalDescription && modalClose && modalReturn);
  let returnFocusTarget = null;

  // Opens an informational dialog only. Never fetches, updates a door or awards anything.
  function openLockedPreview(day, opener) {
    if (!canShowPreview) return;
    returnFocusTarget = opener;
    modalDay.textContent = day === 24 ? 'December 24 · Christmas Eve finale' : 'December ' + day;
    modalDescription.textContent = day === 24
      ? 'The grand Christmas Eve door is preparing a very cheesy finale! This is only a preview: no reward is unlocked, reserved or granted.'
      : 'A festive surprise is planned behind this door. It remains locked: no reward is unlocked, reserved or granted in preview mode.';
    modal.hidden = false;
    modalClose.focus();
  }

  // Closes the informational dialog and restores focus to the selected door.
  function closeLockedPreview() {
    if (!canShowPreview || modal.hidden) return;
    modal.hidden = true;
    if (returnFocusTarget && typeof returnFocusTarget.focus === 'function') {
      returnFocusTarget.focus();
    }
    returnFocusTarget = null;
  }

  if (canShowPreview) {
    modalClose.addEventListener('click', closeLockedPreview);
    modalReturn.addEventListener('click', closeLockedPreview);
    modal.addEventListener('click', (event) => {
      if (event.target && event.target.hasAttribute('data-christmas26-dismiss')) {
        closeLockedPreview();
      }
    });
    modal.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeLockedPreview();
        return;
      }
      if (event.key !== 'Tab') return;
      // Exactly two focusable controls: close and return.
      if (event.shiftKey && document.activeElement === modalClose) {
        event.preventDefault();
        modalReturn.focus();
      } else if (!event.shiftKey && document.activeElement === modalReturn) {
        event.preventDefault();
        modalClose.focus();
      }
    });
  }

  const icons = ['🎁', '🧀', '❄️', '🎄', '⭐', '🧦'];
  const fragment = document.createDocumentFragment();
  for (let day = 1; day <= 24; day += 1) {
    const item = document.createElement('div');
    item.className = 'christmas26-day' + (day === 24 ? ' christmas26-day--finale' : '');
    item.setAttribute('role', 'listitem');

    const door = document.createElement('button');
    door.type = 'button';
    door.className = 'christmas26-door';
    // The click opens only a locked informational preview. Missing modal fails closed.
    door.disabled = !canShowPreview;
    door.setAttribute('aria-label', 'December ' + day + (day === 24 ? ', Christmas Eve finale' : '') + ': locked; view festive preview; claims unavailable');
    if (canShowPreview) {
      door.addEventListener('click', () => openLockedPreview(day, door));
    }

    const icon = document.createElement('span');
    icon.className = 'christmas26-door-symbol';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = day === 24 ? '🌟' : icons[(day - 1) % icons.length];

    const number = document.createElement('span');
    number.className = 'christmas26-door-day';
    number.textContent = String(day);

    const state = document.createElement('span');
    state.className = 'christmas26-door-state';
    state.textContent = day === 24 ? 'Finale · Locked' : 'Preview · Locked';

    door.append(icon, number, state);
    item.appendChild(door);
    fragment.appendChild(item);
  }
  doors.replaceChildren(fragment);

  // Decorative countdown only. Never determines reward eligibility.
  // December 1, 2026 00:00 Europe/Vienna = November 30 23:00 UTC.
  const openingTimestamp = Date.parse('2026-11-30T23:00:00Z');
  const today = Date.now();
  if (today >= openingTimestamp) {
    countdown.textContent = 'Preview only · reward claims not enabled';
  } else {
    const secondsRemaining = Math.ceil((openingTimestamp - today) / 1000);
    const days = Math.floor(secondsRemaining / 86400);
    const hours = Math.floor((secondsRemaining % 86400) / 3600);
    const minutes = Math.floor((secondsRemaining % 3600) / 60);
    countdown.textContent = days + 'd · ' + hours + 'h · ' + minutes + 'm';
  }
})();
