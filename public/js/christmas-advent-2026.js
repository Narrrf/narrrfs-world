/* 🎄 Christmas Advent 2026 V0 — NON-MUTATING, DISPLAY-ONLY preview.
 * DEVS FOR DECADES: disabled door buttons are intentional. Future backend
 * must determine official Vienna day, eligibility, rewards, and claim state.
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

  const icons = ['🎁', '🧀', '❄️', '🎄', '⭐', '🧦'];
  const fragment = document.createDocumentFragment();
  for (let day = 1; day <= 24; day += 1) {
    const item = document.createElement('div');
    item.className = 'christmas26-day' + (day === 24 ? ' christmas26-day--finale' : '');
    item.setAttribute('role', 'listitem');

    const door = document.createElement('button');
    door.type = 'button';
    door.className = 'christmas26-door';
    door.disabled = true;
    door.setAttribute('aria-label', 'December ' + day + (day === 24 ? ', Christmas Eve finale' : '') + ': preview only; claims unavailable');

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
