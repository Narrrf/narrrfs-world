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

  const authState = {
    checked: false,
    loggedIn: false,
    discordId: '',
    discordName: ''
  };

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

  async function verifySession() {
    try {
      const response = await fetch('/api/user/get-session.php', {
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

      authState.checked = true;
      authState.loggedIn = discordId !== '';
      authState.discordId = discordId;
      authState.discordName = discordUsername || getStoredDiscordName();

      if (authState.loggedIn) {
        window.sessionDiscordId = discordId;
        window.sessionDiscordUsername = discordUsername || '';

        localStorage.setItem('discord_id', discordId);
        localStorage.setItem('auth_timestamp', String(Date.now()));

        if (authState.discordName) {
          localStorage.setItem('discord_name', authState.discordName);
        }

        return;
      }

      window.sessionDiscordId = '';
      window.sessionDiscordUsername = '';
      clearStoredAuth();
    } catch (error) {
      console.error('❌ Cheese auth session verification failed:', error);

      authState.checked = true;
      authState.loggedIn = false;
      authState.discordId = '';
      authState.discordName = '';

      window.sessionDiscordId = '';
      window.sessionDiscordUsername = '';
      clearStoredAuth();
    }
  }

  function removeExistingIndicator() {
    const existing = document.getElementById('cheese-auth-indicator');
    if (existing) {
      existing.remove();
    }
  }

  function renderIndicator() {
    removeExistingIndicator();

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
  }

  function toggleProfileBanner() {
    const prominentBanner = document.getElementById('discord-login-prominent');
    if (!prominentBanner) return;

    if (authState.loggedIn) {
      prominentBanner.classList.add('hidden');
      prominentBanner.setAttribute('aria-hidden', 'true');
      return;
    }

    prominentBanner.classList.remove('hidden');
    prominentBanner.removeAttribute('aria-hidden');
  }

  async function syncAuthUi() {
    await verifySession();
    renderIndicator();
    toggleProfileBanner();
  }

  document.addEventListener('DOMContentLoaded', syncAuthUi);
  window.addEventListener('focus', syncAuthUi);
  window.addEventListener('storage', syncAuthUi);

  window.NarrrfsCheeseAuth = {
    refresh: syncAuthUi
  };
})();