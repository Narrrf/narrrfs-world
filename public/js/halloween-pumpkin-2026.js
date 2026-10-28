/* 🎃 Halloween 2026 profile experience.
 * DEVS FOR DECADES: all reward, claim, identity, and expiry decisions belong to
 * the server. Never display a winning popup before an authoritative credit reply.
 */
(() => {
  'use strict';
  const section = document.getElementById('halloween-pumpkin-2026');
  if (!section) return;

  const art = document.getElementById('halloween26-art');
  const claim = document.getElementById('halloween26-claim');
  const status = document.getElementById('halloween26-status');
  const login = document.getElementById('halloween26-login');
  const modal = document.getElementById('halloween26-modal');
  const modalAmount = document.getElementById('halloween26-modal-amount');
  const modalClose = document.getElementById('halloween26-modal-close');
  const apiUrl = '/api/user/halloween-pumpkin-2026.php';
  const eventKey = 'halloween_pumpkin_2026';
  let csrfToken = null;
  let busy = false;
  let claimed = false;
  let pendingReveal = null;

  const formatAmount = amount => Number(amount).toLocaleString('en-US') + ' DSPOINC';

  /** Refresh authoritative claim eligibility; never infer it from localStorage. */
  async function refreshStatus() {
    const response = await fetch(apiUrl, {credentials: 'include', cache: 'no-store', headers:{Accept:'application/json'}});
    // Disabled or expired events are not displayed publicly.
    // The server remains authoritative for claims and rewards.
    if (response.status === 404 || response.status === 410) {
      section.hidden = true;
      return null;
    }
    if (response.status === 401) {
      section.hidden = false;
      login.hidden = false;
      claim.disabled = true;
      art.disabled = true;
      status.textContent = 'Log in with Discord to claim your Halloween gift.';
      return null;
    }
    if (!response.ok) throw new Error('Halloween eligibility unavailable (HTTP ' + response.status + ')');
    const result = await response.json();
    if (!result.success || result.event !== eventKey) throw new Error('Unexpected Halloween status response');
    section.hidden = !result.active;
    if (!result.active) return result;
    login.hidden = true;
    claimed = !!result.claimed;
    csrfToken = typeof result.csrf_token === 'string' ? result.csrf_token : null;
    claim.disabled = claimed || !csrfToken;
    art.disabled = claimed || !csrfToken;
    claim.textContent = claimed ? '🎃 Already Claimed' : '🎃 Wake the Pumpkin!';
    status.textContent = claimed
      ? 'Your one-time Halloween gift: ' + formatAmount(result.amount) + '. Check Recent Transactions!'
      : 'One gift per Discord account. Available through October 31, 2026, 23:59:59 Vienna time.';
    return result;
  }

  /** Show the accessible success dialog only after the server confirms credit. */
  function showReward(amount) {
    modalAmount.textContent = formatAmount(amount);
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    modalClose.focus();
  }

  function closeReward() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    claim.focus();
  }

  /** Post the fresh session CSRF token and consume one server-managed claim. */
  async function claimPumpkin() {
    if (busy || claimed || !csrfToken) return;
    busy = true;
    const token = csrfToken;
    csrfToken = null;
    claim.disabled = true;
    art.disabled = true;
    status.textContent = 'Waking the pumpkin and verifying your reward…';
    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'include',
        cache: 'no-store',
        headers: {'Content-Type': 'application/json', 'Accept':'application/json', 'X-CSRF-Token': token},
        body: JSON.stringify({event: eventKey})
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success || result.claimed !== true) {
        throw new Error('Claim was not confirmed (HTTP ' + response.status + ')');
      }
      claimed = true;
      claim.textContent = '🎃 Already Claimed';
      status.textContent = 'Halloween gift recorded: ' + formatAmount(result.amount) + '. Check Recent Transactions!';
      if (!result.already_claimed) {
        section.querySelector('.halloween26-card').classList.add('is-celebrating');
        art.classList.add('is-wiggling');
        const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        pendingReveal = window.setTimeout(() => {
          section.querySelector('.halloween26-card').classList.remove('is-celebrating');
          art.classList.remove('is-wiggling');
          showReward(result.amount);
          pendingReveal = null;
        }, reducedMotion ? 0 : 1700);
      }
      // Credit confirmed: ask Profile to reload read-only history.
      window.dispatchEvent(new Event('narrrfs:halloween26-credit-confirmed'));
      if (typeof loadRewardChamber === 'function') {
        void loadRewardChamber(true);
      }
    } catch (error) {
      console.error('Halloween reward confirmation unavailable:', error);
      status.textContent = 'Unable to confirm the reward. Checking the server before allowing another attempt…';
      try {
        await refreshStatus();
      } catch (statusError) {
        console.error('Halloween follow-up status unavailable:', statusError);
        status.textContent = 'Claim status is uncertain. Refresh the page before trying again. Do not repeatedly click.';
        claim.disabled = true;
        art.disabled = true;
      }
    } finally {
      busy = false;
    }
  }

  claim.addEventListener('click', claimPumpkin);
  art.addEventListener('click', claimPumpkin);
  modalClose.addEventListener('click', closeReward);
  modal.addEventListener('click', event => { if (event.target === modal) closeReward(); });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !modal.hidden) closeReward();
  });
  window.addEventListener('pagehide', () => {
    if (pendingReveal !== null) window.clearTimeout(pendingReveal);
  });
  refreshStatus().catch(error => {
    console.error('Halloween pumpkin status unavailable:', error);
    section.hidden = true; // fail closed; no unusable claim controls
  });
})();
