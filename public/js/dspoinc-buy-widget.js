/**
 * Narrrfs dSPOINC Buy Widget.
 *
 * Plain language for DEVS FOR DECADES:
 * This file is frontend UI only. It lets a logged-in player enter a DSPOINC
 * amount, request an authoritative backend quote, and start the protected
 * SOL -> SPOINC -> DSPOINC bridge flow.
 *
 * This file must never credit DSPOINC, decide settlement, bypass Gensuki,
 * bypass Solana verification, or write economy state. The backend remains
 * authoritative for quote, transaction payload, confirmation, and credit.
 */
(function () {
  'use strict';

  const DEFAULT_DSPOINC_AMOUNT = 1000000;
  const MIN_DSPOINC_AMOUNT = 10000;
  const MAX_DSPOINC_AMOUNT = 100000000;
  const DSPOINC_PER_SPOINC = 10000;
  const NATIVE_SOL_ADDRESS = '11111111111111111111111111111111';
  const RESUME_STORAGE_PREFIX = 'narrrfs_dspoinc_buy_resume_v1';
  const SOLANA_WEB3_SRC = 'https://unpkg.com/@solana/web3.js@1.98.4/lib/index.iife.min.js';

  const ENDPOINTS = Object.freeze({
    quote: '/api/partner/spoinc/quote-dspoinc-buy.php',
    buyCreate: '/api/partner/spoinc/create-gensuki-buy-intent.php',
    buyConfirm: '/api/partner/spoinc/confirm-gensuki-buy-intent.php',
    depositCreate: '/api/partner/spoinc/create-spoinc-to-dspoinc-deposit-intent.php',
    depositConfirm: '/api/partner/spoinc/confirm-spoinc-to-dspoinc-deposit.php'
  });

  const WIDGET_SELECTOR = '.narrrfs-dspoinc-buy-widget';
  const BUTTON_SELECTOR = '.narrrfs-dspoinc-buy-btn';

  let solanaWeb3LoadPromise = null;

  function getApiBaseUrl() {
    return window.location.hostname === 'narrrfs.world'
      ? 'https://narrrfs.world'
      : '';
  }

  function buildApiUrl(path) {
    return `${getApiBaseUrl()}${path}`;
  }

  function normalizeInteger(value, fallbackValue) {
    const numericValue = Number(String(value || '').replace(/[^\d]/g, ''));

    if (!Number.isFinite(numericValue)) {
      return fallbackValue;
    }

    return Math.round(numericValue);
  }

  function normalizeDecimalString(value) {
    const text = String(value ?? '').trim();

    if (!/^\d+(\.\d+)?$/.test(text)) {
      return '';
    }

    return text;
  }

  function formatNumber(value) {
    return Number(value || 0).toLocaleString('en-US');
  }

  function formatBridgeAmount(value) {
    const numericValue = Number(value || 0);

    if (!Number.isFinite(numericValue)) {
      return String(value || '0');
    }

    return numericValue.toLocaleString('en-US', {
      maximumFractionDigits: 9
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function resolveDiscordId() {
    return String(
      window.sessionDiscordId ||
      window.currentUser?.discord_id ||
      window.currentAdminUser?.discord_id ||
      localStorage.getItem('discord_id') ||
      localStorage.getItem('narrrfs_last_discord_id') ||
      ''
    ).trim();
  }

  function resolveDiscordName() {
    return String(
      window.sessionDiscordUsername ||
      window.currentUser?.username ||
      window.currentAdminUser?.username ||
      localStorage.getItem('discord_name') ||
      localStorage.getItem('narrrfs_last_discord_name') ||
      localStorage.getItem('DISCORD_NAME') ||
      ''
    ).trim();
  }

  async function ensureSolanaWeb3() {
    if (window.solanaWeb3) {
      return window.solanaWeb3;
    }

    if (!solanaWeb3LoadPromise) {
      solanaWeb3LoadPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector(`script[src="${SOLANA_WEB3_SRC}"]`);

        if (existingScript) {
          existingScript.addEventListener('load', () => resolve(window.solanaWeb3));
          existingScript.addEventListener('error', () => reject(new Error('Solana Web3 failed to load.')));
          return;
        }

        const script = document.createElement('script');
        script.src = SOLANA_WEB3_SRC;
        script.async = true;

        script.onload = () => {
          if (!window.solanaWeb3) {
            reject(new Error('Solana Web3 loaded but did not expose window.solanaWeb3.'));
            return;
          }

          resolve(window.solanaWeb3);
        };

        script.onerror = () => reject(new Error('Solana Web3 failed to load.'));

        document.head.appendChild(script);
      });
    }

    const loaded = await solanaWeb3LoadPromise;

    if (!loaded) {
      throw new Error('Solana Web3 is unavailable.');
    }

    return loaded;
  }

  async function connectWallet() {
    const provider = window.solana;

    if (!provider || !provider.isPhantom) {
      throw new Error('Phantom wallet not found. Please install/open Phantom.');
    }

    const response = await provider.connect();
    const publicKey = response?.publicKey?.toBase58
      ? response.publicKey.toBase58()
      : String(response?.publicKey || '');

    if (!publicKey) {
      throw new Error('Could not read Phantom wallet address.');
    }

    return publicKey;
  }

  async function requestJson(path, payload) {
    const response = await fetch(buildApiUrl(path), {
      method: 'POST',
      credentials: 'include',
      cache: 'no-store',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const text = await response.text();
    let data = null;

    try {
      data = text ? JSON.parse(text) : null;
    } catch (error) {
      throw new Error(`Backend returned non-JSON response: ${text.slice(0, 160)}`);
    }

    if (!response.ok || data?.success === false) {
      throw new Error(data?.error || data?.message || `HTTP ${response.status}`);
    }

    return data;
  }

  /**
   * Decode a base64 transaction payload returned by Gensuki.
   *
   * DEVS FOR DECADES:
   * Gensuki may return either a legacy Solana Transaction or a VersionedTransaction.
   * Frontend only signs/sends. Frontend never credits DSPOINC.
   */
  async function decodeGensukiTransactionPayload(transactionPayload) {
    const solanaWeb3 = await ensureSolanaWeb3();

    if (!transactionPayload || typeof transactionPayload !== 'string') {
      throw new Error('Gensuki did not return a transaction payload.');
    }

    const rawTransactionBytes = Uint8Array.from(
      atob(transactionPayload),
      (character) => character.charCodeAt(0)
    );

    if (solanaWeb3.VersionedTransaction) {
      try {
        return solanaWeb3.VersionedTransaction.deserialize(rawTransactionBytes);
      } catch (versionedError) {
        // Legacy payloads are tried below.
      }
    }

    if (!solanaWeb3.Transaction || !solanaWeb3.Transaction.from) {
      throw new Error('Solana Web3 legacy transaction parser is unavailable.');
    }

    return solanaWeb3.Transaction.from(rawTransactionBytes);
  }

  async function sendGensukiTransactionWithPhantom(intentData, label) {
    if (!window.solana || !window.solana.isPhantom) {
      throw new Error('Phantom wallet is required for this bridge flow.');
    }

    const transactionPayload = intentData.transaction || intentData.gensuki_transaction || intentData.transaction_base64 || '';
    if (!transactionPayload) {
      throw new Error(`Gensuki did not return a signable ${label} transaction payload.`);
    }

    const connectedWallet = await connectWallet();
    const expectedWallet = String(intentData.wallet || intentData.buyer_wallet || '').trim();

    if (expectedWallet && connectedWallet !== expectedWallet) {
      throw new Error(`Connected Phantom wallet does not match ${label} intent wallet. Expected ${expectedWallet}`);
    }

    const transaction = await decodeGensukiTransactionPayload(transactionPayload);

    if (window.solana.signAndSendTransaction) {
      const result = await window.solana.signAndSendTransaction(transaction);
      const signature = result && result.signature ? String(result.signature) : '';

      if (!signature) {
        throw new Error(`Phantom did not return a ${label} transaction signature.`);
      }

      return signature;
    }

    if (!window.solana.signTransaction) {
      throw new Error('Phantom signTransaction is unavailable.');
    }

    const signedTransaction = await window.solana.signTransaction(transaction);
    const solanaWeb3 = await ensureSolanaWeb3();

    if (!solanaWeb3.Connection) {
      throw new Error('Solana Web3 connection is unavailable.');
    }

    const connection = new solanaWeb3.Connection('https://api.mainnet-beta.solana.com', 'confirmed');

    return await connection.sendRawTransaction(signedTransaction.serialize());
  }

  function buildResumeStorageKey(discordId, wallet, dspoincAmount) {
    return `${RESUME_STORAGE_PREFIX}:${discordId}:${wallet}:${dspoincAmount}`;
  }

  function readResumeState(discordId, wallet, dspoincAmount) {
    const key = buildResumeStorageKey(discordId, wallet, dspoincAmount);

    try {
      const parsed = JSON.parse(localStorage.getItem(key) || 'null');

      if (!parsed || typeof parsed !== 'object') {
        return { key, state: null };
      }

      if (
        String(parsed.discordId || '') !== String(discordId) ||
        String(parsed.wallet || '') !== String(wallet) ||
        Number(parsed.dspoincAmount || 0) !== Number(dspoincAmount || 0)
      ) {
        return { key, state: null };
      }

      return { key, state: parsed };
    } catch (error) {
      return { key, state: null };
    }
  }

  function writeResumeState(key, statePatch) {
    let existing = {};

    try {
      existing = JSON.parse(localStorage.getItem(key) || '{}') || {};
    } catch (error) {
      existing = {};
    }

    const nextState = {
      ...existing,
      ...statePatch,
      updatedAt: new Date().toISOString()
    };

    localStorage.setItem(key, JSON.stringify(nextState));

    return nextState;
  }

  function clearResumeState(key) {
    localStorage.removeItem(key);
  }

  function summarizeResumeState(state) {
    if (!state) {
      return '';
    }

    if (state.depositSignature) {
      return 'Resume found: SPOINC → dSPOINC signature already exists. The widget will retry final backend confirmation.';
    }

    if (state.depositIntentId) {
      return 'Resume found: SPOINC → dSPOINC intent already exists. The widget will continue from the second wallet signature.';
    }

    if (state.buyConfirmed) {
      return 'Resume found: SOL → SPOINC is confirmed. The widget will continue to SPOINC → dSPOINC.';
    }

    if (state.buySignature) {
      return 'Resume found: SOL → SPOINC signature already exists. The widget will retry backend buy confirmation without creating a new SOL buy.';
    }

    if (state.buyIntentId) {
      return 'Resume found: SOL → SPOINC intent exists but no signature was saved. The widget may reuse the saved transaction payload.';
    }

    return '';
  }

  function createWidgetHtml(defaultDspoinc) {
    return `
      <section class="rounded-3xl border border-yellow-300/30 bg-slate-950/90 p-5 shadow-2xl shadow-yellow-500/10 text-white">
        <div class="flex items-center gap-3 mb-4">
          <div class="text-3xl">🧀</div>
          <div>
            <div class="text-xs uppercase tracking-[0.18em] text-yellow-200 font-black">Buy dSPOINC</div>
            <h3 class="text-2xl font-black text-white">Buy dSPOINC with SOL</h3>
          </div>
        </div>

        <p class="text-sm text-slate-300 mb-4">
          This live bridge uses two protected wallet signatures: first SOL → SPOINC, then SPOINC → dSPOINC.
          dSPOINC is credited only after backend confirmation succeeds.
        </p>

        <label class="block text-xs font-black uppercase tracking-[0.14em] text-yellow-200 mb-2">
          dSPOINC amount
        </label>

        <div class="flex flex-col sm:flex-row gap-3">
          <input
            data-dspoinc-buy-amount
            type="text"
            inputmode="numeric"
            autocomplete="off"
            spellcheck="false"
            aria-label="dSPOINC amount"
            placeholder="Example: 100,000"
            value="${escapeHtml(formatDspoincInputValue(defaultDspoinc))}"
            class="w-full rounded-2xl border border-yellow-300/25 bg-black/45 px-4 py-3 text-white font-black outline-none focus:border-yellow-300"
          />

          <button
            type="button"
            data-dspoinc-buy-quote
            class="rounded-2xl bg-yellow-400 px-5 py-3 font-black text-black hover:bg-yellow-300 transition"
          >
            Quote
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 text-sm">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
            <div class="text-slate-400 text-xs">You receive</div>
            <div data-dspoinc-buy-receive class="font-black text-green-300">${formatNumber(defaultDspoinc)} dSPOINC</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
            <div class="text-slate-400 text-xs">Bridge amount</div>
            <div data-dspoinc-buy-spoinc class="font-black text-cyan-300">${formatBridgeAmount(defaultDspoinc / DSPOINC_PER_SPOINC)} SPOINC</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
            <div class="text-slate-400 text-xs">Estimated SOL</div>
            <div data-dspoinc-buy-sol class="font-black text-yellow-300">Quote needed</div>
          </div>
        </div>

        <button
          type="button"
          data-dspoinc-buy-start
          class="mt-5 w-full rounded-2xl bg-gradient-to-r from-yellow-400 via-orange-400 to-pink-500 px-5 py-4 font-black text-black shadow-lg hover:scale-[1.01] transition disabled:opacity-50 disabled:cursor-not-allowed"
          disabled
        >
          🧀 Start 2-step buy
        </button>

        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-3">1️⃣ Sign SOL → SPOINC</div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-3">2️⃣ Sign SPOINC → dSPOINC</div>
        </div>

        <div data-dspoinc-buy-status class="mt-4 rounded-2xl border border-white/10 bg-black/35 p-3 text-sm text-slate-300">
          Enter an amount and request a quote.
        </div>
      </section>
    `;
  }

  function setStatus(widget, message, tone) {
    const statusEl = widget.querySelector('[data-dspoinc-buy-status]');
    if (!statusEl) {
      return;
    }

    const toneClass = tone === 'error'
      ? 'text-red-200 border-red-400/30'
      : tone === 'success'
        ? 'text-green-200 border-green-400/30'
        : tone === 'info'
          ? 'text-yellow-100 border-yellow-400/30'
          : 'text-slate-300 border-white/10';

    statusEl.className = `mt-4 rounded-2xl border bg-black/35 p-3 text-sm ${toneClass}`;
    statusEl.innerHTML = message;
  }

  /**
   * Parse a user-entered dSPOINC amount without fighting the cursor.
   *
   * Plain language for DEVS FOR DECADES:
   * The buy widget accepts visual grouping characters because users naturally
   * type amounts like 100,000 / 100.000 / 100 000. dSPOINC is still submitted
   * as one whole integer amount. This is frontend input cleanup only.
   *
   * This helper must never quote, sign, settle, credit, debit, or confirm.
   */
  function parseDspoincAmountInput(value, fallbackAmount = DEFAULT_DSPOINC_AMOUNT) {
    const rawValue = String(value ?? '').trim();

    if (!rawValue) {
      return normalizeInteger(fallbackAmount, DEFAULT_DSPOINC_AMOUNT);
    }

    const digitsOnly = rawValue.replace(/[^0-9]/g, '');

    if (!digitsOnly) {
      return normalizeInteger(fallbackAmount, DEFAULT_DSPOINC_AMOUNT);
    }

    return normalizeInteger(digitsOnly, fallbackAmount);
  }

  /**
   * Format the amount for readability only when the user is not actively typing.
   */
  function formatDspoincInputValue(value) {
    return formatNumber(normalizeInteger(value, DEFAULT_DSPOINC_AMOUNT));
  }

  function updateLocalEstimate(widget, options = {}) {
    const input = widget.querySelector('[data-dspoinc-buy-amount]');
    const amount = parseDspoincAmountInput(input?.value, DEFAULT_DSPOINC_AMOUNT);
    const safeAmount = Math.min(MAX_DSPOINC_AMOUNT, Math.max(MIN_DSPOINC_AMOUNT, amount));
    const spoincAmount = safeAmount / DSPOINC_PER_SPOINC;
    const shouldCommitInput = Boolean(options?.commitInput);

    if (input && shouldCommitInput) {
      input.value = formatDspoincInputValue(safeAmount);
    }

    widget.querySelector('[data-dspoinc-buy-receive]').textContent = `${formatNumber(safeAmount)} dSPOINC`;
    widget.querySelector('[data-dspoinc-buy-spoinc]').textContent = `${formatBridgeAmount(spoincAmount)} SPOINC`;
    widget.querySelector('[data-dspoinc-buy-sol]').textContent = 'Quote needed';

    widget.dataset.dspoincAmount = String(safeAmount);
    widget.dataset.spoincAmount = String(spoincAmount);

    delete widget.dataset.quoteToken;
    delete widget.dataset.estimatedSolAmount;
    delete widget.dataset.wallet;
    delete widget.dataset.buyIntentId;
    delete widget.dataset.depositIntentId;

    const startButton = widget.querySelector('[data-dspoinc-buy-start]');
    if (startButton) {
      startButton.disabled = true;
    }
  }

  async function quoteDspoincBuy(widget) {
    updateLocalEstimate(widget, { commitInput: true });

    const dspoincAmount = normalizeInteger(widget.dataset.dspoincAmount, DEFAULT_DSPOINC_AMOUNT);
    const discordId = resolveDiscordId();

    if (!discordId) {
      throw new Error('Discord login required before buying dSPOINC.');
    }

    setStatus(widget, 'Requesting bridge quote…', 'info');

    const wallet = await connectWallet();

    const data = await requestJson(ENDPOINTS.quote, {
      request_type: 'quote_dspoinc_buy_with_sol',
      discord_id: discordId,
      discord_name: resolveDiscordName(),
      wallet,
      dspoinc_amount: dspoincAmount
    });

    const quote = data?.data || data;
    const estimatedSol = normalizeDecimalString(
      quote.estimated_sol_amount ?? quote.sol_amount ?? quote.payment_amount_sol
    );
    const spoincAmount = normalizeDecimalString(
      quote.spoinc_amount ?? String(dspoincAmount / DSPOINC_PER_SPOINC)
    );

    widget.dataset.wallet = wallet;
    widget.dataset.quoteToken = String(quote.quote_token || quote.quote_id || '');
    widget.dataset.spoincAmount = String(spoincAmount);
    widget.dataset.estimatedSolAmount = String(estimatedSol || '');

    widget.querySelector('[data-dspoinc-buy-spoinc]').textContent = `${formatBridgeAmount(spoincAmount)} SPOINC`;
    widget.querySelector('[data-dspoinc-buy-sol]').textContent = estimatedSol
      ? `${estimatedSol} SOL`
      : 'SOL quote unavailable';

    const startButton = widget.querySelector('[data-dspoinc-buy-start]');
    if (startButton) {
      startButton.disabled = !estimatedSol;
    }

    setStatus(
      widget,
      `Quote ready for <strong>${formatNumber(dspoincAmount)} dSPOINC</strong>.<br>` +
        `Pay estimate: <strong>${escapeHtml(estimatedSol || 'SOL unavailable')} SOL</strong><br>` +
        `Wallet: <code>${escapeHtml(wallet)}</code>`,
      'success'
    );
  }

  async function startDspoincBuy(widget) {
    updateLocalEstimate(widget, { commitInput: true });

    const dspoincAmount = normalizeInteger(widget.dataset.dspoincAmount, DEFAULT_DSPOINC_AMOUNT);
    const discordId = resolveDiscordId();
    const wallet = widget.dataset.wallet || await connectWallet();
    const estimatedSolAmount = normalizeDecimalString(widget.dataset.estimatedSolAmount || '');
    const spoincAmount = normalizeDecimalString(widget.dataset.spoincAmount || '');

    if (!discordId) {
      throw new Error('Discord login required before buying dSPOINC.');
    }

    if (!estimatedSolAmount || !spoincAmount) {
      throw new Error('Please request a fresh SOL quote before buying.');
    }

    const { key: resumeKey, state: existingResumeState } = readResumeState(discordId, wallet, dspoincAmount);
    let resumeState = existingResumeState || null;

    if (resumeState) {
      const resumeMessage = summarizeResumeState(resumeState);
      if (resumeMessage) {
        setStatus(widget, `${escapeHtml(resumeMessage)}<br>Continuing safely…`, 'info');
      }
    }

    let buyIntent = resumeState?.buyIntent || null;
    let buyIntentId = Number(resumeState?.buyIntentId || 0);
    let buySignature = String(resumeState?.buySignature || '');
    let buyConfirm = resumeState?.buyConfirm || null;

    if (!buySignature && buyIntentId && buyIntent) {
      setStatus(
        widget,
        'Resume: SOL → SPOINC intent exists. Phantom will ask you to sign the saved transaction payload.',
        'info'
      );

      buySignature = await sendGensukiTransactionWithPhantom(buyIntent, 'SOL → SPOINC buy');

      resumeState = writeResumeState(resumeKey, {
        discordId,
        wallet,
        dspoincAmount,
        spoincAmount,
        estimatedSolAmount,
        buyIntentId,
        buyIntent,
        buySignature,
        stage: 'buy_signature_returned'
      });
    }

    if (!buyIntentId && !buySignature) {
      setStatus(
        widget,
        `Step 1/5: Creating SOL → SPOINC buy intent for <strong>${escapeHtml(estimatedSolAmount)} SOL</strong>…`,
        'info'
      );

      const buyCreateResponse = await requestJson(ENDPOINTS.buyCreate, {
        request_type: 'create_sol_to_spoinc_for_dspoinc_buy',
        discord_id: discordId,
        discord_name: resolveDiscordName(),
        wallet,
        payment_amount: estimatedSolAmount,
        payment_token_address: NATIVE_SOL_ADDRESS,
        target_dspoinc_amount: dspoincAmount,
        target_spoinc_amount: spoincAmount,
        quote_token: widget.dataset.quoteToken || null
      });

      buyIntent = buyCreateResponse?.data || buyCreateResponse;
      buyIntentId = Number(buyIntent.intent_id || 0);

      if (!buyIntentId) {
        throw new Error('Backend did not return a SOL → SPOINC buy intent id.');
      }

      widget.dataset.buyIntentId = String(buyIntentId);

      resumeState = writeResumeState(resumeKey, {
        discordId,
        wallet,
        dspoincAmount,
        spoincAmount,
        estimatedSolAmount,
        buyIntentId,
        buyIntent,
        stage: 'buy_payload_ready'
      });

      setStatus(
        widget,
        'Step 2/5: Phantom will ask you to sign the SOL → SPOINC transaction. This does not credit dSPOINC yet.',
        'info'
      );

      buySignature = await sendGensukiTransactionWithPhantom(buyIntent, 'SOL → SPOINC buy');

      resumeState = writeResumeState(resumeKey, {
        buySignature,
        stage: 'buy_signature_returned'
      });
    }

    if (!buySignature) {
      throw new Error('SOL → SPOINC signature is missing. Please reconnect Phantom and resume.');
    }

    if (!resumeState?.buyConfirmed) {
      setStatus(
        widget,
        `Step 3/5: Confirming SOL → SPOINC buy with backend…<br><code>${escapeHtml(buySignature)}</code>`,
        'info'
      );

      const buyConfirmResponse = await requestJson(ENDPOINTS.buyConfirm, {
        request_type: 'confirm_sol_to_spoinc_for_dspoinc_buy',
        discord_id: discordId,
        discord_name: resolveDiscordName(),
        wallet,
        intent_id: buyIntentId,
        transactionHash: buySignature,
        transaction_hash: buySignature,
        signature: buySignature,
        status: 'complete',
        target_dspoinc_amount: dspoincAmount,
        target_spoinc_amount: spoincAmount
      });

      buyConfirm = buyConfirmResponse?.data || buyConfirmResponse;

      resumeState = writeResumeState(resumeKey, {
        buyConfirm,
        buyConfirmed: true,
        stage: 'buy_confirmed_no_dspoinc_yet'
      });
    } else {
      buyConfirm = resumeState.buyConfirm || {};
    }

    let depositIntent = resumeState?.depositIntent || null;
    let depositIntentId = Number(resumeState?.depositIntentId || 0);
    let depositSignature = String(resumeState?.depositSignature || '');

    if (!depositSignature && depositIntentId && depositIntent) {
      setStatus(
        widget,
        'Resume: SPOINC → dSPOINC intent exists. Phantom will ask you to sign the saved transaction payload.',
        'info'
      );

      depositSignature = await sendGensukiTransactionWithPhantom(depositIntent, 'SPOINC → dSPOINC deposit');

      resumeState = writeResumeState(resumeKey, {
        depositSignature,
        stage: 'deposit_signature_returned'
      });
    }

    if (!depositIntentId && !depositSignature) {
      setStatus(
        widget,
        `Step 4/5: SPOINC buy confirmed. Creating SPOINC → dSPOINC deposit intent for <strong>${escapeHtml(spoincAmount)} SPOINC</strong>…`,
        'info'
      );

      const depositCreateResponse = await requestJson(ENDPOINTS.depositCreate, {
        request_type: 'create_spoinc_to_dspoinc_after_sol_buy',
        discord_id: discordId,
        discord_name: resolveDiscordName(),
        wallet,
        amount: spoincAmount,
        spoinc_amount: spoincAmount,
        dspoinc_amount: dspoincAmount,
        source_buy_intent_id: buyIntentId,
        source_buy_transaction_hash: buySignature,
        buy_confirm_status: buyConfirm.status || buyConfirm.gensuki_status || ''
      });

      depositIntent = depositCreateResponse?.data || depositCreateResponse;
      depositIntentId = Number(depositIntent.intent_id || 0);

      if (!depositIntentId) {
        throw new Error('Backend did not return a SPOINC → dSPOINC deposit intent id.');
      }

      widget.dataset.depositIntentId = String(depositIntentId);

      resumeState = writeResumeState(resumeKey, {
        depositIntentId,
        depositIntent,
        stage: 'deposit_payload_ready'
      });

      setStatus(
        widget,
        'Step 5/5: Phantom will ask you to sign the SPOINC → dSPOINC transaction. Backend credits dSPOINC only after this confirmation succeeds.',
        'info'
      );

      depositSignature = await sendGensukiTransactionWithPhantom(depositIntent, 'SPOINC → dSPOINC deposit');

      resumeState = writeResumeState(resumeKey, {
        depositSignature,
        stage: 'deposit_signature_returned'
      });
    }

    if (!depositSignature) {
      throw new Error('SPOINC → dSPOINC signature is missing. Please reconnect Phantom and resume.');
    }

    setStatus(
      widget,
      `Final confirmation running…<br><code>${escapeHtml(depositSignature)}</code>`,
      'info'
    );

    const depositConfirmResponse = await requestJson(ENDPOINTS.depositConfirm, {
      request_type: 'confirm_spoinc_to_dspoinc_after_sol_buy',
      discord_id: discordId,
      discord_name: resolveDiscordName(),
      wallet,
      intent_id: depositIntentId,
      transactionHash: depositSignature,
      transaction_hash: depositSignature,
      signature: depositSignature,
      status: 'complete',
      dspoinc_amount: dspoincAmount,
      spoinc_amount: spoincAmount,
      source_buy_intent_id: buyIntentId,
      source_buy_transaction_hash: buySignature
    });

    const depositConfirm = depositConfirmResponse?.data || depositConfirmResponse;

    clearResumeState(resumeKey);

    setStatus(
      widget,
      `✅ dSPOINC buy flow complete.<br>` +
        `Requested credit: <strong>${formatNumber(dspoincAmount)} dSPOINC</strong><br>` +
        `SPOINC deposit: <strong>${escapeHtml(spoincAmount)} SPOINC</strong><br>` +
        `Backend response: <pre class="mt-3 overflow-auto text-xs">${escapeHtml(JSON.stringify(depositConfirm, null, 2))}</pre>`,
      'success'
    );
  }

  function initializeWidget(widget) {
    if (widget.dataset.dspoincBuyInitialized === 'true') {
      return;
    }

    widget.dataset.dspoincBuyInitialized = 'true';

    const defaultAmount = normalizeInteger(
      widget.dataset.defaultDspoinc,
      DEFAULT_DSPOINC_AMOUNT
    );

    widget.innerHTML = createWidgetHtml(defaultAmount);
    updateLocalEstimate(widget, { commitInput: true });

    const input = widget.querySelector('[data-dspoinc-buy-amount]');
    const quoteButton = widget.querySelector('[data-dspoinc-buy-quote]');
    const startButton = widget.querySelector('[data-dspoinc-buy-start]');

    input?.addEventListener('focus', () => {
      if (input.dataset.dspoincBuyUserEdited === 'true') {
        return;
      }

      // Select the default formatted amount so users can immediately type
      // 250000 / 250,000 / 250.000 without first deleting 10,000.
      input.select();
    });

    input?.addEventListener('input', () => {
      input.dataset.dspoincBuyUserEdited = 'true';
      updateLocalEstimate(widget);
    });

    input?.addEventListener('blur', () => updateLocalEstimate(widget, { commitInput: true }));

    quoteButton?.addEventListener('click', async () => {
      try {
        quoteButton.disabled = true;
        await quoteDspoincBuy(widget);
      } catch (error) {
        setStatus(widget, escapeHtml(error.message || error), 'error');
      } finally {
        quoteButton.disabled = false;
      }
    });

    startButton?.addEventListener('click', async () => {
      try {
        startButton.disabled = true;
        await startDspoincBuy(widget);
      } catch (error) {
        setStatus(widget, escapeHtml(error.message || error), 'error');
      } finally {
        startButton.disabled = false;
      }
    });
  }

  function initializeButton(button) {
    if (button.dataset.dspoincBuyButtonInitialized === 'true') {
      return;
    }

    button.dataset.dspoincBuyButtonInitialized = 'true';

    button.addEventListener('click', () => {
      const amount = normalizeInteger(button.dataset.dspoincAmount, DEFAULT_DSPOINC_AMOUNT);

      let widget = document.querySelector(WIDGET_SELECTOR);

      if (!widget) {
        widget = document.createElement('div');
        widget.className = 'narrrfs-dspoinc-buy-widget';
        widget.dataset.defaultDspoinc = String(amount);
        button.insertAdjacentElement('afterend', widget);
        initializeWidget(widget);
      }

      const input = widget.querySelector('[data-dspoinc-buy-amount]');
      if (input) {
        input.value = formatDspoincInputValue(amount);
        delete input.dataset.dspoincBuyUserEdited;
        updateLocalEstimate(widget, { commitInput: true });
      }

      widget.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }

  function initializeDspoincBuyWidgets() {
    document.querySelectorAll(WIDGET_SELECTOR).forEach(initializeWidget);
    document.querySelectorAll(BUTTON_SELECTOR).forEach(initializeButton);
  }

  window.NarrrfsDspoincBuyWidget = {
    initialize: initializeDspoincBuyWidgets
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDspoincBuyWidgets);
  } else {
    initializeDspoincBuyWidgets();
  }
})();
