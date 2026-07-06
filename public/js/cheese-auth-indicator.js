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
  const MUSIC_STORAGE_KEY = 'narrrfs_music_enabled';
  const SFX_STORAGE_KEY = 'narrrfs_sfx_enabled';

/**
 * Reads a Narrrfs audio preference with safe default ON behavior.
 *
 * Plain language for DEVS:
 * Missing localStorage keys should mean ON so returning players and new players
 * keep the same game behavior they had before this split Music/SFX upgrade.
 */
function readNarrrfsAudioPreference(key) {
  return localStorage.getItem(key) !== 'false';
}

/**
 * Writes one Narrrfs audio preference as a stable string value.
 */
function writeNarrrfsAudioPreference(key, enabled) {
  localStorage.setItem(key, enabled ? 'true' : 'false');
}

/**
 * Global Narrrfs audio controller.
 *
 * Plain language for DEVS:
 * Music and SFX are now separate player preferences.
 * - Music controls looping MP3 background tracks.
 * - SFX controls Web Audio / short audio effects.
 *
 * The old window.NarrrfsSound object stays below as a compatibility bridge for
 * older pages that still expect one shared sound setting.
 */
window.NarrrfsAudio = window.NarrrfsAudio || {
  isMusicEnabled() {
    return readNarrrfsAudioPreference(MUSIC_STORAGE_KEY);
  },

  setMusicEnabled(enabled) {
    writeNarrrfsAudioPreference(MUSIC_STORAGE_KEY, enabled);

    window.dispatchEvent(new CustomEvent('narrrfs:music-toggle', {
      detail: { enabled }
    }));

    window.dispatchEvent(new CustomEvent('narrrfs:sound-toggle', {
      detail: {
        enabled: this.isMusicEnabled() && this.isSfxEnabled(),
        musicEnabled: this.isMusicEnabled(),
        sfxEnabled: this.isSfxEnabled()
      }
    }));
  },

  toggleMusic() {
    const nextEnabled = !this.isMusicEnabled();
    this.setMusicEnabled(nextEnabled);
    return nextEnabled;
  },

  isSfxEnabled() {
    return readNarrrfsAudioPreference(SFX_STORAGE_KEY);
  },

  setSfxEnabled(enabled) {
    writeNarrrfsAudioPreference(SFX_STORAGE_KEY, enabled);

    window.dispatchEvent(new CustomEvent('narrrfs:sfx-toggle', {
      detail: { enabled }
    }));

    window.dispatchEvent(new CustomEvent('narrrfs:sound-toggle', {
      detail: {
        enabled: this.isMusicEnabled() && this.isSfxEnabled(),
        musicEnabled: this.isMusicEnabled(),
        sfxEnabled: this.isSfxEnabled()
      }
    }));
  },

  toggleSfx() {
    const nextEnabled = !this.isSfxEnabled();
    this.setSfxEnabled(nextEnabled);
    return nextEnabled;
  },

  areAllAudioChannelsEnabled() {
    return this.isMusicEnabled() && this.isSfxEnabled();
  },

  setAllEnabled(enabled) {
    writeNarrrfsAudioPreference(MUSIC_STORAGE_KEY, enabled);
    writeNarrrfsAudioPreference(SFX_STORAGE_KEY, enabled);
    writeNarrrfsAudioPreference(SOUND_STORAGE_KEY, enabled);

    window.dispatchEvent(new CustomEvent('narrrfs:music-toggle', {
      detail: { enabled }
    }));

    window.dispatchEvent(new CustomEvent('narrrfs:sfx-toggle', {
      detail: { enabled }
    }));

    window.dispatchEvent(new CustomEvent('narrrfs:sound-toggle', {
      detail: {
        enabled,
        musicEnabled: enabled,
        sfxEnabled: enabled
      }
    }));
  }
};

/**
 * Compatibility bridge for old pages.
 *
 * Plain language for DEVS:
 * Old code can still call window.NarrrfsSound.isEnabled().
 * New code should use:
 * - window.NarrrfsAudio.isMusicEnabled()
 * - window.NarrrfsAudio.isSfxEnabled()
 */
window.NarrrfsSound = window.NarrrfsSound || {
  isEnabled() {
  // Compatibility bridge for older game SFX checks.
  //
  // DEVS FOR DECADES:
  // After the Music/SFX split, old NarrrfsSound.isEnabled() calls should behave
  // like SFX permission. New MP3 music controllers must use
  // window.NarrrfsAudio.isMusicEnabled() directly.
  if (window.NarrrfsAudio && typeof window.NarrrfsAudio.isSfxEnabled === 'function') {
    return window.NarrrfsAudio.isSfxEnabled();
  }

  return readNarrrfsAudioPreference(SOUND_STORAGE_KEY);
},

  setEnabled(enabled) {
    writeNarrrfsAudioPreference(SOUND_STORAGE_KEY, enabled);

    if (window.NarrrfsAudio && typeof window.NarrrfsAudio.setAllEnabled === 'function') {
      window.NarrrfsAudio.setAllEnabled(enabled);
      return;
    }

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

        // Remove existing audio controls (prevent duplicates)
    const existingSoundBtn = document.getElementById('narrrfs-sound-toggle');
    if (existingSoundBtn) {
      existingSoundBtn.remove();
    }

    const existingAudioControls = document.getElementById('narrrfs-audio-controls');
    if (existingAudioControls) {
      existingAudioControls.remove();
    }

    const loggedIn = authState.loggedIn;
    const discordName = authState.discordName || '';

    const wrapper = document.createElement('div');
    wrapper.id = 'cheese-auth-indicator';
    wrapper.setAttribute('data-auth-state', loggedIn ? 'logged_in' : 'logged_out');
        // Keep the shared auth pill out of page/game controls.
    //
    // DEVS FOR DECADES:
    // This indicator is shared across normal pages, Lab pages, and game pages.
    // It must never cover native video controls, game controls, wallet buttons,
    // Stake Lab action buttons, or Lab floating actions. This is visual-only and
    // does not change login state, Discord session checks, roles, rewards, scores,
    // audio preferences, or backend APIs.
    wrapper.style.position = 'fixed';
    wrapper.style.right = '16px';
    wrapper.style.bottom = '22px';
    wrapper.style.zIndex = '9400';
    wrapper.style.maxWidth = 'min(320px, calc(100vw - 32px))';

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

        // 🎚️ Create separated Music / SFX controls.
    // DEVS FOR DECADES:
    // This visual control is shared across Narrrfs pages. It only stores local
    // player audio preferences and dispatches browser events. It does not touch
    // login, roles, scores, rewards, APIs, or database writes.
    const audioControls = document.createElement('div');
    audioControls.id = 'narrrfs-audio-controls';
        // Stack audio controls above the auth pill without covering page actions.
    //
    // DEVS FOR DECADES:
    // The Music/SFX controls are shared player preferences only.
    // They must sit above the login/profile pill and stay compact on pages like
    // lab.html and stake-lab.html where users already have bottom/right controls.
    audioControls.style.position = 'fixed';
    audioControls.style.right = '16px';
    audioControls.style.bottom = '92px';
    audioControls.style.zIndex = '9400';
    audioControls.style.display = 'flex';
    audioControls.style.flexDirection = 'column';
    audioControls.style.gap = '6px';
    audioControls.style.alignItems = 'flex-end';
    audioControls.style.maxWidth = 'min(190px, calc(100vw - 32px))';

    function styleAudioButton(button) {
      button.type = 'button';
            button.style.minWidth = '92px';
      button.style.padding = '6px 10px';
      button.style.borderRadius = '999px';
      button.style.fontSize = '11px';
      button.style.fontWeight = '800';
      button.style.border = '1px solid rgba(250, 204, 21, 0.55)';
      button.style.background = 'rgba(15, 23, 42, 0.88)';
      button.style.color = '#fde68a';
      button.style.cursor = 'pointer';
      button.style.backdropFilter = 'blur(10px)';
      button.style.boxShadow = '0 10px 24px rgba(0, 0, 0, 0.28)';
      button.style.transition = 'transform 0.18s ease, border-color 0.18s ease, color 0.18s ease';
    }

    function updateAudioButtonVisual(button, enabled, enabledText, disabledText) {
      button.textContent = enabled ? enabledText : disabledText;
      button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      button.style.opacity = enabled ? '1' : '0.72';
      button.style.borderColor = enabled
        ? 'rgba(250, 204, 21, 0.62)'
        : 'rgba(148, 163, 184, 0.42)';
      button.style.color = enabled ? '#fde68a' : '#cbd5e1';
    }

    const musicButton = document.createElement('button');
    musicButton.id = 'narrrfs-music-toggle';
    musicButton.title = 'Toggle background music';
    styleAudioButton(musicButton);

    const sfxButton = document.createElement('button');
    sfxButton.id = 'narrrfs-sfx-toggle';
    sfxButton.title = 'Toggle sound effects';
    styleAudioButton(sfxButton);

    function refreshAudioButtons() {
      updateAudioButtonVisual(
        musicButton,
        window.NarrrfsAudio.isMusicEnabled(),
        '🎵 Music ON',
        '🔇 Music OFF'
      );

      updateAudioButtonVisual(
        sfxButton,
        window.NarrrfsAudio.isSfxEnabled(),
        '🔊 SFX ON',
        '🔕 SFX OFF'
      );
    }

    musicButton.addEventListener('mouseenter', () => {
      musicButton.style.transform = 'translateY(-1px) scale(1.03)';
    });

    musicButton.addEventListener('mouseleave', () => {
      musicButton.style.transform = 'none';
    });

    sfxButton.addEventListener('mouseenter', () => {
      sfxButton.style.transform = 'translateY(-1px) scale(1.03)';
    });

    sfxButton.addEventListener('mouseleave', () => {
      sfxButton.style.transform = 'none';
    });

    musicButton.onclick = () => {
      window.NarrrfsAudio.toggleMusic();
      refreshAudioButtons();
    };

    sfxButton.onclick = () => {
      window.NarrrfsAudio.toggleSfx();
      refreshAudioButtons();
    };

    refreshAudioButtons();

    audioControls.appendChild(musicButton);
    audioControls.appendChild(sfxButton);
    document.body.appendChild(audioControls);
        // Mobile and embedded-view anti-overlap guard.
    //
    // DEVS FOR DECADES:
    // Discord previews, narrow screens, and browser video overlays can make the
    // bottom-right corner crowded. Move the shared controls slightly up and make
    // the auth pill compact without touching the actual auth/session logic.
    if (window.matchMedia('(max-width: 768px)').matches) {
      wrapper.style.right = '10px';
      wrapper.style.bottom = '72px';
      wrapper.style.transform = 'scale(0.88)';
      wrapper.style.transformOrigin = 'bottom right';

      audioControls.style.right = '10px';
      audioControls.style.bottom = '142px';
      audioControls.style.transform = 'scale(0.88)';
      audioControls.style.transformOrigin = 'bottom right';
    }

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