(function () {
  const DISCORD_AUTH_URL =
    'https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=guilds+identify+guilds.members.read';

  const AUTH_STORAGE_KEYS = [
    'discord_id',
    'discord_name',
    'narrrfs_last_discord_id',
    'narrrfs_last_discord_name',
    'DISCORD_NAME',
    'auth_timestamp'
  ];

  const SESSION_ENDPOINT = '/api/user/get-session.php';
  const VERIFY_THROTTLE_MS = 1500;
  const FOCUS_VERIFY_MIN_INTERVAL_MS = 3000;

  const authState = {
    checked: false,
    loggedIn: false,
    discordId: '',
    discordName: ''
  };

  let activeVerifyPromise = null;
  let lastVerifyAt = 0;
  let lastSuccessfulVerifyAt = 0;
  let lastKnownDiscordId = '';
  let pendingRefreshTimeout = null;

  function clearStoredAuth() {
    AUTH_STORAGE_KEYS.forEach((key) => {
      localStorage.removeItem(key);
      sessionStorage.removeItem(key);
    });
  }

  function getStoredDiscordName() {
    return String(
      localStorage.getItem('discord_name') ||
      localStorage.getItem('narrrfs_last_discord_name') ||
      localStorage.getItem('DISCORD_NAME') ||
      sessionStorage.getItem('discord_name') ||
      ''
    ).trim();
  }

  function getStoredDiscordId() {
    return String(
      localStorage.getItem('discord_id') ||
      localStorage.getItem('narrrfs_last_discord_id') ||
      sessionStorage.getItem('discord_id') ||
      window.sessionDiscordId ||
      ''
    ).trim();
  }

  function applyLoggedInState(discordId, discordUsername) {
    const resolvedName = String(discordUsername || getStoredDiscordName()).trim();

    authState.checked = true;
    authState.loggedIn = true;
    authState.discordId = discordId;
    authState.discordName = resolvedName;

    lastKnownDiscordId = discordId;
    lastSuccessfulVerifyAt = Date.now();

    window.sessionDiscordId = discordId;
    window.sessionDiscordUsername = resolvedName || '';

    localStorage.setItem('discord_id', discordId);
    localStorage.setItem('narrrfs_last_discord_id', discordId);
    localStorage.setItem('auth_timestamp', String(Date.now()));

    if (resolvedName) {
      localStorage.setItem('discord_name', resolvedName);
      localStorage.setItem('narrrfs_last_discord_name', resolvedName);
      localStorage.setItem('DISCORD_NAME', resolvedName);
    }
  }

  function applyLoggedOutState(options = {}) {
    const { clearStorage = true } = options;

    authState.checked = true;
    authState.loggedIn = false;
    authState.discordId = '';
    authState.discordName = '';

    lastKnownDiscordId = '';

    window.sessionDiscordId = '';
    window.sessionDiscordUsername = '';

    if (clearStorage) {
      clearStoredAuth();
    }
  }

  async function verifySession(options = {}) {
    const { force = false, source = 'unknown' } = options;
    const now = Date.now();

    if (!force && activeVerifyPromise) {
      return activeVerifyPromise;
    }

    if (!force && (now - lastVerifyAt) < VERIFY_THROTTLE_MS) {
      return authState;
    }

    lastVerifyAt = now;

    activeVerifyPromise = (async () => {
      try {
        const response = await fetch(SESSION_ENDPOINT, {
          method: 'GET',
          credentials: 'include',
          cache: 'no-store'
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        const discordId = String(data?.discord_id || '').trim();
        const discordUsername = String(data?.discord_username || '').trim();

        if (discordId) {
          applyLoggedInState(discordId, discordUsername);
          return authState;
        }

        // Only apply logged-out state on confirmed empty session.
        applyLoggedOutState({ clearStorage: true });
        return authState;
      } catch (error) {
        console.error(`❌ Cheese auth session verification failed (${source}):`, error);

        // Important:
        // Do NOT destroy local auth state on temporary fetch/network/session timing issues.
        // Keep last known stable state if we had one before.
        if (lastKnownDiscordId || getStoredDiscordId()) {
          const fallbackDiscordId = lastKnownDiscordId || getStoredDiscordId();
          const fallbackDiscordName = getStoredDiscordName();

          authState.checked = true;
          authState.loggedIn = fallbackDiscordId !== '';
          authState.discordId = fallbackDiscordId;
          authState.discordName = fallbackDiscordName;

          window.sessionDiscordId = fallbackDiscordId;
          window.sessionDiscordUsername = fallbackDiscordName || '';

          return authState;
        }

        applyLoggedOutState({ clearStorage: false });
        return authState;
      } finally {
        activeVerifyPromise = null;
      }
    })();

    return activeVerifyPromise;
  }

  function removeExistingIndicator() {
    const existing = document.getElementById('cheese-auth-indicator');
    if (existing) {
      existing.remove();
    }
  }

  const SOUND_STORAGE_KEY = 'narrrfs_sound_enabled';

/**
 * Global Narrrfs sound controller.
 * This lets every page and game respect one shared sound preference.
 */
window.NarrrfsSound = window.NarrrfsSound || {
  isEnabled() {
    return localStorage.getItem(SOUND_STORAGE_KEY) !== 'false';
  },

  setEnabled(enabled) {
    localStorage.setItem(SOUND_STORAGE_KEY, enabled ? 'true' : 'false');

    window.dispatchEvent(new CustomEvent('narrrfs:sound-toggle', {
      detail: { enabled }
    }));
  },

  toggle() {
    const nextEnabled = !this.isEnabled();
    this.setEnabled(nextEnabled);
    return nextEnabled;
  }
};

  function renderIndicator() {
    removeExistingIndicator();

    // Remove existing sound toggle (prevent duplicates)
const existingSoundBtn = document.getElementById('narrrfs-sound-toggle');
if (existingSoundBtn) {
  existingSoundBtn.remove();
}

    const loggedIn = authState.loggedIn;
    const discordName = authState.discordName || '';

    const wrapper = document.createElement('div');
    wrapper.id = 'cheese-auth-indicator';
    wrapper.setAttribute('data-auth-state', loggedIn ? 'logged_in' : 'logged_out');
    wrapper.style.position = 'fixed';
    wrapper.style.right = '16px';
    wrapper.style.bottom = '16px';
    wrapper.style.zIndex = '9999';

    const link = document.createElement('a');
    link.href = loggedIn ? 'profile.html' : DISCORD_AUTH_URL;
    link.setAttribute('data-auth-link', loggedIn ? 'profile' : 'discord');
    link.className =
      'group flex items-center gap-3 rounded-full border px-4 py-3 shadow-2xl backdrop-blur-md transition-all duration-300 hover:scale-105';

    if (loggedIn) {
      link.classList.add(
        'bg-gradient-to-r',
        'from-emerald-500/90',
        'to-green-600/90',
        'border-emerald-300/60',
        'text-white'
      );
      link.title = discordName ? `Logged in as ${discordName}` : 'Logged in with Discord';
    } else {
      link.classList.add(
        'bg-gradient-to-r',
        'from-yellow-500/90',
        'to-orange-500/90',
        'border-yellow-200/70',
        'text-slate-900'
      );
      link.title = 'Login with Discord';
    }

    const icon = document.createElement('span');
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '🧀';
    icon.style.fontSize = '1.5rem';
    icon.style.lineHeight = '1';

    const textWrap = document.createElement('span');
    textWrap.style.display = 'flex';
    textWrap.style.flexDirection = 'column';
    textWrap.style.lineHeight = '1.1';

    const line1 = document.createElement('span');
    line1.style.fontWeight = '800';
    line1.style.fontSize = '0.9rem';
    line1.textContent = loggedIn ? 'Logged in' : 'Discord Login';

    const line2 = document.createElement('span');
    line2.style.fontSize = '0.72rem';
    line2.style.opacity = '0.9';
    line2.textContent = loggedIn
      ? (discordName || 'Profile ready')
      : 'Tap to authenticate';

    textWrap.appendChild(line1);
    textWrap.appendChild(line2);

    link.appendChild(icon);
    link.appendChild(textWrap);
    wrapper.appendChild(link);
    document.body.appendChild(wrapper);

    // 🔊 Create Sound Toggle Button
const soundButton = document.createElement('button');
soundButton.id = 'narrrfs-sound-toggle';
soundButton.type = 'button';
soundButton.style.position = 'fixed';
soundButton.style.right = '16px';
soundButton.style.bottom = '80px'; // above auth button
soundButton.style.zIndex = '9999';
soundButton.style.padding = '6px 10px';
soundButton.style.borderRadius = '999px';
soundButton.style.fontSize = '12px';
soundButton.style.border = '1px solid rgba(250, 204, 21, 0.5)';
soundButton.style.background = 'rgba(15, 23, 42, 0.85)';
soundButton.style.color = '#fde68a';
soundButton.style.cursor = 'pointer';
soundButton.style.backdropFilter = 'blur(6px)';

// Initial state
const enabled = window.NarrrfsSound?.isEnabled();
soundButton.textContent = enabled ? '🔊 Sound ON' : '🔇 Sound OFF';

// Toggle behavior
soundButton.onclick = () => {
  const next = window.NarrrfsSound.toggle();
  soundButton.textContent = next ? '🔊 Sound ON' : '🔇 Sound OFF';
};

// Add to page
document.body.appendChild(soundButton);

  }

  function toggleProfileBanner() {
    const prominentBanner = document.getElementById('discord-login-prominent');
    if (!prominentBanner) {
      return;
    }

    if (authState.loggedIn) {
      prominentBanner.classList.add('hidden');
      prominentBanner.setAttribute('aria-hidden', 'true');
      return;
    }

    prominentBanner.classList.remove('hidden');
    prominentBanner.removeAttribute('aria-hidden');
  }

  async function syncAuthUi(options = {}) {
    await verifySession(options);
    renderIndicator();
    toggleProfileBanner();
    return authState;
  }

  function scheduleRefresh(options = {}) {
    const delay = typeof options.delay === 'number' ? options.delay : 120;
    const refreshOptions = {
      force: Boolean(options.force),
      source: String(options.source || 'scheduled')
    };

    if (pendingRefreshTimeout) {
      window.clearTimeout(pendingRefreshTimeout);
    }

    pendingRefreshTimeout = window.setTimeout(() => {
      pendingRefreshTimeout = null;
      syncAuthUi(refreshOptions);
    }, delay);
  }

  function handleWindowFocus() {
    const now = Date.now();
    if ((now - lastSuccessfulVerifyAt) < FOCUS_VERIFY_MIN_INTERVAL_MS) {
      return;
    }

    scheduleRefresh({
      force: false,
      source: 'focus',
      delay: 100
    });
  }

  function handleStorageEvent(event) {
    if (!event || !AUTH_STORAGE_KEYS.includes(String(event.key || ''))) {
      return;
    }

    // Re-render after page-level auth writers settle.
    scheduleRefresh({
      force: true,
      source: 'storage',
      delay: 250
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    syncAuthUi({
      force: true,
      source: 'DOMContentLoaded'
    });
  });

  window.addEventListener('focus', handleWindowFocus);
  window.addEventListener('storage', handleStorageEvent);

  window.NarrrfsCheeseAuth = {
    refresh(options = {}) {
      return syncAuthUi({
        force: Boolean(options.force),
        source: String(options.source || 'manual_refresh')
      });
    },
    scheduleRefresh,
    getState() {
      return { ...authState };
    }
  };
})();