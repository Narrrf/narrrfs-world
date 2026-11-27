/* 🎄 Christmas Advent 2026 V1 — Profile 24-door calendar UI.
 * Uses ONLY server-authoritative /api/user/christmas-advent-2026-claim-context.php
 * and POST /api/user/christmas-advent-2026-claim.php with empty JSON `{}` and CSRF.
 * Never submits client amounts, item IDs, probability rolls, or account identity.
 * Falls back cleanly to locked preview when backend flags are OFF (HTTP 404).
 */
(() => {
  'use strict';
  const calendar = document.getElementById('christmas-advent-2026');
  if (!calendar) return;

  const doors = calendar.querySelector('#christmas26-door-grid');
  const countdown = calendar.querySelector('#christmas26-countdown');
  const progressBox = calendar.querySelector('.christmas26-progress');
  const footerBox = calendar.querySelector('.christmas26-footer');
  if (!doors || !countdown) return;

  const modal = document.getElementById('christmas26-modal');
  const modalCard = modal ? modal.querySelector('.christmas26-modal-card') : null;
  const modalEmblem = modal ? modal.querySelector('.christmas26-modal-emblem') : null;
  const modalKicker = modal ? modal.querySelector('.christmas26-modal-kicker') : null;
  const modalTitle = document.getElementById('christmas26-modal-title');
  const modalDay = document.getElementById('christmas26-modal-day');
  const modalDescription = document.getElementById('christmas26-modal-description');
  const modalLocked = modal ? modal.querySelector('.christmas26-modal-locked') : null;
  const modalClose = document.getElementById('christmas26-modal-close');
  const modalReturn = document.getElementById('christmas26-modal-return');
  const canShowModal = Boolean(modal && modalCard && modalDay && modalDescription && modalClose && modalReturn);

  let receiptBox = null;
  let claimActionBtn = null;
  if (canShowModal) {
    receiptBox = document.createElement('div');
    receiptBox.id = 'christmas26-modal-receipt';
    receiptBox.className = 'christmas26-modal-receipt';
    receiptBox.hidden = true;

    claimActionBtn = document.createElement('button');
    claimActionBtn.type = 'button';
    claimActionBtn.id = 'christmas26-modal-claim';
    claimActionBtn.className = 'christmas26-modal-claim';
    claimActionBtn.hidden = true;

    modalCard.insertBefore(receiptBox, modalReturn);
    modalCard.insertBefore(claimActionBtn, modalReturn);
  }

  let returnFocusTarget = null;
  let activeModalDay = null;
  let isClaimPending = false;
  let serverState = {
    mode: 'preview', // 'preview' | 'unauthenticated' | 'unavailable' | 'live'
    claimsEnabled: false,
    csrfToken: null,
    eventState: 'pre_event',
    doorMap: new Map()
  };

  function claimErrorMessage(code) {
    const map = {
      claim_unavailable: 'Daily claims are currently unavailable.',
      claim_policy_unavailable: 'Advent reward policy is not active yet.',
      event_not_started: 'Advent opens on December 1 (Europe/Vienna time).',
      event_expired: 'This Christmas Advent event has ended.',
      daily_claim_limit_reached: 'Today’s daily claim limit has already been reached.',
      budget_exhausted: 'Today’s reward pool capacity has been reached.',
      authentication_required: 'Please sign in with Discord first.',
      csrf_rejected: 'Session token refreshed. Please click Claim again.',
      database_busy: 'Server busy with holiday traffic. Please try again.'
    };
    return map[code] || 'Could not complete this claim right now. Please try again.';
  }

  function formatDspoinc(amount) {
    const n = Number(amount || 0);
    return Number.isFinite(n) ? n.toLocaleString('en-US') : String(amount || 0);
  }

  function renderReceiptSummary(receipt) {
    if (!receiptBox || !receipt) return;
    receiptBox.replaceChildren();

    const rows = [
      ['🪙 dSPOINC Awarded', `+${formatDspoinc(receipt.reward_dspoinc)} dSPOINC`],
      [
        '🎁 Bonus Prize',
        receipt.bonus_awarded && receipt.bonus_item
          ? `${receipt.bonus_item.quantity || 1}x ${String(receipt.bonus_item.item_name || 'Store Item')}`
          : 'No bonus item this time'
      ],
      ['✅ Status', `Claim saved (#${receipt.claim_id})`]
    ];
    for (const [label, val] of rows) {
      const row = document.createElement('div');
      row.className = 'christmas26-modal-receipt-row';
      const k = document.createElement('span');
      k.textContent = label;
      const v = document.createElement('strong');
      v.textContent = val;
      row.append(k, v);
      receiptBox.appendChild(row);
    }
    receiptBox.hidden = false;
  }

  function updateModalForDay(day) {
    if (!canShowModal) return;
    activeModalDay = day;
    const doorData = serverState.doorMap.get(day) || { state: 'locked', receipt: null };
    const state = serverState.mode === 'live' ? doorData.state : 'locked';
    const isFinale = day === 24;
    modalDay.textContent = isFinale ? 'December 24 · Christmas Eve finale' : `December ${day}`;

    if (receiptBox) receiptBox.hidden = true;
    if (claimActionBtn) {
      claimActionBtn.hidden = true;
      claimActionBtn.disabled = false;
      claimActionBtn.textContent = '🎁 Open Door & Claim Reward';
    }

    if (state === 'claimed' && doorData.receipt) {
      if (modalEmblem) modalEmblem.textContent = '🎄';
      if (modalKicker) modalKicker.textContent = '✅ REWARD CONFIRMED & SAVED';
      if (modalTitle) modalTitle.textContent = `December ${day} Claimed!`;
      modalDescription.textContent = doorData.receipt.replayed
        ? 'Your saved Advent reward was retrieved safely from your claim receipt.'
        : 'Your daily Advent reward is credited and saved to your account.';
      renderReceiptSummary(doorData.receipt);
      if (modalLocked) {
        modalLocked.textContent = doorData.receipt.claimed_at
          ? `✅ Claim saved · ${doorData.receipt.claimed_at} UTC`
          : '✅ Claim saved on server';
      }
      return;
    }

    if (state === 'available' && serverState.claimsEnabled) {
      if (modalEmblem) modalEmblem.textContent = '🎁';
      if (modalKicker) modalKicker.textContent = '✨ DOOR READY TO OPEN';
      if (modalTitle) modalTitle.textContent = isFinale ? 'Open the Christmas Eve Finale!' : `Open December ${day}!`;
      modalDescription.textContent = 'Claim today’s server-rolled dSPOINC reward plus a chance at an automatic Store bonus.';
      if (modalLocked) {
        modalLocked.textContent = '🕯️ One claim per account per Europe/Vienna calendar day.';
      }
      if (claimActionBtn) {
        claimActionBtn.hidden = false;
        claimActionBtn.disabled = isClaimPending;
      }
      return;
    }

    if (modalEmblem) modalEmblem.textContent = state === 'missed' ? '❄️' : '🎁';
    if (serverState.mode === 'unauthenticated') {
      if (modalKicker) modalKicker.textContent = '❄️ MOUSEGANG CHRISTMAS';
      if (modalTitle) modalTitle.textContent = 'Sign in to view your Advent calendar';
      modalDescription.textContent = 'Sign in with Discord to load your verified December progress and daily claims.';
      if (modalLocked) modalLocked.textContent = '🔒 Discord login required';
    } else if (serverState.mode === 'unavailable') {
      if (modalKicker) modalKicker.textContent = '❄️ MOUSEGANG CHRISTMAS';
      if (modalTitle) modalTitle.textContent = 'Advent status temporarily unavailable';
      modalDescription.textContent = 'Could not verify server claim status right now. No rewards were modified.';
      if (modalLocked) modalLocked.textContent = '⚠️ Unavailable · please refresh shortly';
    } else if (state === 'missed') {
      if (modalKicker) modalKicker.textContent = '❄️ CALENDAR DAY PASSED';
      if (modalTitle) modalTitle.textContent = `December ${day} missed`;
      modalDescription.textContent = 'This calendar day has already passed in Europe/Vienna time.';
      if (modalLocked) modalLocked.textContent = '🔒 Missed calendar window';
    } else if (state === 'available') {
      if (modalKicker) modalKicker.textContent = '❄️ DOOR STATUS';
      if (modalTitle) modalTitle.textContent = `December ${day} · Claims Paused`;
      modalDescription.textContent = 'Today’s calendar day is active, but owner reward claims are currently turned off.';
      if (modalLocked) modalLocked.textContent = '🔒 Claims not currently active';
    } else {
      if (modalKicker) modalKicker.textContent = '❄️ MOUSEGANG CHRISTMAS · PREVIEW';
      if (modalTitle) modalTitle.textContent = 'A little Christmas magic is coming!';
      modalDescription.textContent = isFinale
        ? 'The grand Christmas Eve door opens on December 24 (Europe/Vienna time).'
        : 'A festive surprise waits behind this door until its December calendar day opens.';
      if (modalLocked) modalLocked.textContent = '🔒 Locked · waits for its Europe/Vienna calendar day';
    }
  }

  function openDoorModal(day, opener) {
    if (!canShowModal) return;
    returnFocusTarget = opener;
    updateModalForDay(day);
    modal.hidden = false;
    if (claimActionBtn && !claimActionBtn.hidden) {
      claimActionBtn.focus();
    } else {
      modalClose.focus();
    }
  }

  function closeDoorModal() {
    if (!canShowModal || modal.hidden) return;
    modal.hidden = true;
    activeModalDay = null;
    if (returnFocusTarget && typeof returnFocusTarget.focus === 'function') {
      returnFocusTarget.focus();
    }
    returnFocusTarget = null;
  }

  async function refreshClaimContext() {
    try {
      const response = await fetch('/api/user/christmas-advent-2026-claim-context.php', {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json' }
      });

      if (response.status === 404) {
        serverState = { mode: 'preview', claimsEnabled: false, csrfToken: null, eventState: 'pre_event', doorMap: new Map() };
        renderCalendar();
        return;
      }
      if (response.status === 401) {
        serverState = { mode: 'unauthenticated', claimsEnabled: false, csrfToken: null, eventState: 'pre_event', doorMap: new Map() };
        renderCalendar();
        return;
      }
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload || payload.success !== true || !Array.isArray(payload.doors)) {
        serverState = { mode: 'unavailable', claimsEnabled: false, csrfToken: null, eventState: 'unknown', doorMap: new Map() };
        renderCalendar();
        return;
      }

      const map = new Map();
      for (const door of payload.doors) {
        if (door && Number.isInteger(door.day)) {
          map.set(door.day, door);
        }
      }
      serverState = {
        mode: 'live',
        claimsEnabled: payload.claims_enabled === true,
        csrfToken: typeof payload.csrf_token === 'string' ? payload.csrf_token : null,
        eventState: String(payload.event_state || 'pre_event'),
        doorMap: map
      };
      renderCalendar();
      if (activeModalDay !== null && modal && !modal.hidden) {
        updateModalForDay(activeModalDay);
      }
    } catch (error) {
      serverState = { mode: 'unavailable', claimsEnabled: false, csrfToken: null, eventState: 'unknown', doorMap: new Map() };
      renderCalendar();
    }
  }

  async function submitDailyClaim() {
    if (isClaimPending || activeModalDay === null || !claimActionBtn) return;
    isClaimPending = true;
    claimActionBtn.disabled = true;
    claimActionBtn.textContent = '⏳ Opening Door…';

    try {
      if (!serverState.csrfToken) {
        await refreshClaimContext();
      }
      const csrf = serverState.csrfToken;
      serverState.csrfToken = null;
      if (!csrf) {
        throw new Error('csrf_rejected');
      }

      const res = await fetch('/api/user/christmas-advent-2026-claim.php', {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': csrf
        },
        body: '{}'
      });
      const body = await res.json().catch(() => null);
      if (!res.ok || !body || body.success !== true || !body.claim) {
        throw new Error((body && typeof body.error === 'string') ? body.error : 'claim_unavailable');
      }

      const receipt = body.claim;
      const existing = serverState.doorMap.get(receipt.day_number) || { day: receipt.day_number };
      serverState.doorMap.set(receipt.day_number, {
        ...existing,
        state: 'claimed',
        claimed_at: receipt.claimed_at,
        receipt
      });
      renderCalendar();
      updateModalForDay(receipt.day_number);
    } catch (err) {
      const code = String(err?.message || 'claim_unavailable');
      if (modalLocked) {
        modalLocked.textContent = `❌ ${claimErrorMessage(code)}`;
      }
      await refreshClaimContext();
    } finally {
      isClaimPending = false;
      if (claimActionBtn && !claimActionBtn.hidden) {
        claimActionBtn.disabled = false;
        claimActionBtn.textContent = '🎁 Open Door & Claim Reward';
      }
    }
  }

  if (canShowModal) {
    modalClose.addEventListener('click', closeDoorModal);
    modalReturn.addEventListener('click', closeDoorModal);
    if (claimActionBtn) {
      claimActionBtn.addEventListener('click', submitDailyClaim);
    }
    modal.addEventListener('click', (event) => {
      if (event.target && event.target.hasAttribute('data-christmas26-dismiss')) {
        closeDoorModal();
      }
    });
    modal.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeDoorModal();
        return;
      }
      if (event.key !== 'Tab') return;
      const focusables = [modalClose, claimActionBtn && !claimActionBtn.hidden ? claimActionBtn : null, modalReturn].filter(Boolean);
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  function doorStateText(day, data) {
    if (serverState.mode !== 'live') {
      return day === 24 ? 'Finale · Locked' : 'Preview · Locked';
    }
    const s = data?.state || 'locked';
    if (s === 'claimed') {
      const amt = data?.receipt?.reward_dspoinc;
      return amt ? `Claimed · ${formatDspoinc(amt)}` : 'Claimed';
    }
    if (s === 'available') {
      return serverState.claimsEnabled ? 'Open Today!' : 'Today · Paused';
    }
    if (s === 'missed') {
      return 'Missed';
    }
    return day === 24 ? 'Finale · Locked' : 'Locked';
  }

  function renderCalendar() {
    const icons = ['🎁', '🧀', '❄️', '🎄', '⭐', '🧦'];
    const fragment = document.createDocumentFragment();
    let claimedCount = 0;

    for (let day = 1; day <= 24; day += 1) {
      const doorData = serverState.doorMap.get(day);
      const state = serverState.mode === 'live' ? (doorData?.state || 'locked') : 'locked';
      if (state === 'claimed') claimedCount += 1;

      const item = document.createElement('div');
      item.className = 'christmas26-day'
        + (day === 24 ? ' christmas26-day--finale' : '')
        + (state === 'claimed' ? ' christmas26-day--claimed' : '')
        + (state === 'available' && serverState.claimsEnabled ? ' christmas26-day--available' : '');
      item.setAttribute('role', 'listitem');

      const door = document.createElement('button');
      door.type = 'button';
      door.className = 'christmas26-door';
      door.disabled = !canShowModal;
      door.setAttribute('aria-label', `December ${day}${day === 24 ? ', Christmas Eve finale' : ''}: ${state}`);
      if (canShowModal) {
        door.addEventListener('click', () => openDoorModal(day, door));
      }

      const icon = document.createElement('span');
      icon.className = 'christmas26-door-symbol';
      icon.setAttribute('aria-hidden', 'true');
      icon.textContent = state === 'claimed' ? '✅' : (day === 24 ? '🌟' : icons[(day - 1) % icons.length]);

      const number = document.createElement('span');
      number.className = 'christmas26-door-day';
      number.textContent = String(day);

      const stateEl = document.createElement('span');
      stateEl.className = 'christmas26-door-state';
      stateEl.textContent = doorStateText(day, doorData);

      door.append(icon, number, stateEl);
      item.appendChild(door);
      fragment.appendChild(item);
    }
    doors.replaceChildren(fragment);

    if (progressBox) {
      const countSpan = progressBox.querySelector('span');
      const sub = progressBox.querySelector('small');
      if (countSpan) countSpan.textContent = `${claimedCount} / 24`;
      if (sub) {
        sub.textContent = serverState.mode === 'live'
          ? (serverState.claimsEnabled ? 'Verified claims' : 'Status synced · claims off')
          : 'Preview · no claims';
      }
    }

    if (footerBox && serverState.mode === 'live') {
      const firstNote = footerBox.querySelector('span');
      if (firstNote) {
        firstNote.textContent = serverState.claimsEnabled
          ? '🎄 Daily rewards active: open today’s glowing door for dSPOINC + Store bonus chance.'
          : '🔒 Claims currently paused by owner · verified calendar status only.';
      }
    }
  }

  renderCalendar();

  const openingTimestamp = Date.parse('2026-11-30T23:00:00Z');
  const today = Date.now();
  if (today >= openingTimestamp) {
    countdown.textContent = 'December Advent Calendar · Europe/Vienna';
  } else {
    const secondsRemaining = Math.ceil((openingTimestamp - today) / 1000);
    const days = Math.floor(secondsRemaining / 86400);
    const hours = Math.floor((secondsRemaining % 86400) / 3600);
    const minutes = Math.floor((secondsRemaining % 3600) / 60);
    countdown.textContent = `${days}d · ${hours}h · ${minutes}m`;
  }

  refreshClaimContext();
})();
