(function () {
  const DISCORD_AUTH_URL =
    'https://discord.com/oauth2/authorize?client_id=1357927342265204858&response_type=code&redirect_uri=https%3A%2F%2Fnarrrfs.world%2Fapi%2Fauth%2Fcallback.php&scope=guilds+identify+guilds.members.read';

  function getDiscordSessionId() {
    if (typeof window.sessionDiscordId === 'string' && window.sessionDiscordId.trim() !== '') {
      return window.sessionDiscordId.trim();
    }

    const storedDiscordId = localStorage.getItem('discord_id') || localStorage.getItem('narrrfs_last_discord_id') || '';
    return String(storedDiscordId || '').trim();
  }

  function getDiscordName() {
    const storedName =
      localStorage.getItem('discord_name') ||
      localStorage.getItem('narrrfs_last_discord_name') ||
      localStorage.getItem('DISCORD_NAME') ||
      '';

    return String(storedName || '').trim();
  }

  function isLoggedIn() {
    return getDiscordSessionId() !== '';
  }

  function removeExistingIndicator() {
    const existing = document.getElementById('cheese-auth-indicator');
    if (existing) {
      existing.remove();
    }
  }

  function renderIndicator() {
    removeExistingIndicator();

    const loggedIn = isLoggedIn();
    const discordName = getDiscordName();

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

    if (isLoggedIn()) {
      prominentBanner.classList.add('hidden');
      prominentBanner.setAttribute('aria-hidden', 'true');
      return;
    }

    prominentBanner.classList.remove('hidden');
    prominentBanner.removeAttribute('aria-hidden');
  }

  function syncAuthUi() {
    renderIndicator();
    toggleProfileBanner();
  }

  document.addEventListener('DOMContentLoaded', syncAuthUi);
  window.addEventListener('storage', syncAuthUi);

  window.NarrrfsCheeseAuth = {
    refresh: syncAuthUi
  };
})();