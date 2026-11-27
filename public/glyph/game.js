/*
Glyph Memory — Phase 1 JS
- Menu -> Start -> Board
- Matching logic (2 flips max)
- Timer (starts on game start, stops on win)
- Restart + Menu
- Uses your asset naming:
  Glyphs: assets/glyphs/0.png..9.png and A.png..Z.png
  Backgrounds: assets/backgrounds/bg_easy.jpg, bg_medium.jpg, bg_hard.jpg
  Audio: assets/audio/match.mp3, mismatch.mp3
*/

(() => {
  const memoryStorage = new Map();

  function safeLocalGet(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (_) {
      return memoryStorage.has(key) ? memoryStorage.get(key) : null;
    }
  }

  function safeLocalSet(key, value) {
    const str = String(value);
    memoryStorage.set(key, str);
    try {
      window.localStorage.setItem(key, str);
    } catch (_) {
      // Ignore storage errors in iOS Private Mode or sandboxed WebViews.
    }
  }

  function safeSessionGet(key) {
    try {
      return window.sessionStorage.getItem(key);
    } catch (_) {
      return null;
    }
  }

  function safeSessionSet(key, value) {
    try {
      window.sessionStorage.setItem(key, String(value));
    } catch (_) {
      // Ignore storage errors in iOS Private Mode or sandboxed WebViews.
    }
  }

  // ------- CONFIG -------
  const DIFFICULTIES = {
    easy:   { pairs: 6,  cols: 4 }, // 3x4
    medium: { pairs: 8,  cols: 4 }, // 4x4
    hard:   { pairs: 12, cols: 6 }, // 6x4
  };
  
  // Mobile device detection (January 15, 2026)
  const isMobileDevice =
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
    (window.innerWidth <= 768 && window.matchMedia('(max-width: 768px)').matches);

  // Extra: classify very weak devices as "low-end mobile"
  // Uses deviceMemory / cores / small screen as hints
  const isLowEndMobile = (() => {
    if (!isMobileDevice) return false;

    let mem = 0;
    try {
      if (typeof navigator !== "undefined" && "deviceMemory" in navigator) {
        mem = navigator.deviceMemory || 0;
      }
    } catch (_) {}

    let cores = 0;
    try {
      if (typeof navigator !== "undefined" && navigator.hardwareConcurrency) {
        cores = navigator.hardwareConcurrency;
      }
    } catch (_) {}

    const minSide = Math.min(window.innerWidth || 0, window.innerHeight || 0);

    const lowMem = mem && mem <= 3;       // <= 3 GB
    const lowCores = cores && cores <= 4; // <= 4 cores
    const tinyScreen = minSide > 0 && minSide <= 720; // smaller phones

    return lowMem || (lowCores && tinyScreen) || (tinyScreen && !mem);
  })();

  // Log mobile detection status
  if (isMobileDevice) {
    console.log(
      `📱 [GLYPH] Mobile device detected - using optimized image paths (lowEnd=${isLowEndMobile})`
    );
  } else {
    console.log(`🖥️ [GLYPH] Desktop device - using original image sizes`);
  }
  
    // CRITICAL (January 15, 2026): Mobile image optimization
  // Compressed/resized images for mobile devices to reduce loading time
  // NOTE: Query parameters are added but ignored if no PHP endpoint exists
  // If /api/glyph/compress-image.php exists, it can use these params to resize/encode.
function getOptimizedGlyphPath(originalPath) {
  return String(originalPath || '').replace('assets/glyphs/', 'assets/glyphs-sm/');
}

  // Your glyph filenames:
  const GLYPH_FILES = [
    ...Array.from({ length: 10 }, (_, i) => `assets/glyphs/${i}.png`),
    ...Array.from({ length: 26 }, (_, i) => `assets/glyphs/${String.fromCharCode(65 + i)}.png`),
  ];
  
  // CRITICAL: Verify GLYPH_FILES array is valid and has no duplicates
  (function validateGlyphFiles() {
    const unique = [...new Set(GLYPH_FILES)];
    if (unique.length !== GLYPH_FILES.length) {
      console.error(
        `❌ [GLYPH] GLYPH_FILES array contains duplicates! Total: ${GLYPH_FILES.length}, Unique: ${unique.length}`
      );
      console.error(`   This is a code bug - each glyph should appear exactly once in GLYPH_FILES`);
    }
    if (GLYPH_FILES.length < 12) {
      console.error(
        `❌ [GLYPH] Not enough glyph files! Need at least 12 for hard difficulty, have: ${GLYPH_FILES.length}`
      );
    }
    if (GLYPH_FILES.length === 36) {
      console.log(
        `✅ [GLYPH] Glyph files initialized: ${GLYPH_FILES.length} total (expected 36: 0-9 + A-Z)`
      );
    }
  })();

  // Backgrounds per difficulty (menu uses easy by default)
  const BACKGROUNDS = {
    menu: "assets/backgrounds/bg_easy.jpg",
    easy: "assets/backgrounds/bg_easy.jpg",
    medium: "assets/backgrounds/bg_medium.jpg",
    hard: "assets/backgrounds/bg_hard.jpg",
  };

  // Mobile background optimization (smaller static JPEGs on phones)
  function getOptimizedBackgroundPath(originalPath) {
    if (!isMobileDevice) return originalPath;
    return String(originalPath || '').replace(/\.jpg$/i, '_mobile.jpg');
  }

  // Three sounds: match, mismatch, and card flip
  const SOUNDS = {
    match: "assets/audio/match.mp3",
    fail:  "assets/audio/mismatch.mp3",
    flip:  "assets/audio/flip.mp3", // Sound when card is flipped up
  };

  /**
 * Creates a safe looping background music controller for Narrrfs games.
 *
 * Plain language for DEVS:
 * Browser audio cannot autoplay before a user action. This controller starts
 * only after game start / player interaction, follows the global Narrrfs sound
 * toggle, pauses when requested, and stops when the run ends or returns to menu.
 */
window.NarrrfsGameMusicFactory = window.NarrrfsGameMusicFactory || function createNarrrfsGameMusicController(config) {
  let audio = null;
  let wantsPlayback = false;

  function isSoundAllowed() {
    if (window.NarrrfsAudio && typeof window.NarrrfsAudio.isMusicEnabled === 'function') {
      return window.NarrrfsAudio.isMusicEnabled();
    }

    if (window.NarrrfsSound && typeof window.NarrrfsSound.isEnabled === 'function') {
      return window.NarrrfsSound.isEnabled();
    }

    return safeLocalGet('narrrfs_sound_enabled') !== 'false';
  }

  function getAudio() {
    if (audio) {
      return audio;
    }

    audio = new Audio(config.src);
    audio.loop = true;
    audio.preload = 'auto';
    audio.volume = typeof config.volume === 'number' ? config.volume : 0.22;


    return audio;
  }

  async function start() {
    wantsPlayback = true;

    if (!isSoundAllowed() || document.hidden) {
      pause();
      return;
    }

    try {
      const music = getAudio();
      await music.play();
    } catch (error) {
      console.warn(`🎵 ${config.label} music could not start yet:`, error);
    }
  }

  function pause() {
    if (!audio) {
      return;
    }

    audio.pause();
  }

  function stop() {
    wantsPlayback = false;

    if (!audio) {
      return;
    }

    audio.pause();
    audio.currentTime = 0;
  }

  function sync() {
    if (!wantsPlayback) {
      return;
    }

    if (!isSoundAllowed() || document.hidden) {
      pause();
      return;
    }

    start();
  }

  window.addEventListener('storage', sync);
  window.addEventListener('narrrfs:sound-toggle', sync);
  document.addEventListener('visibilitychange', sync);

  return {
    start,
    pause,
    stop,
    sync
  };
};

/**
 * Returns the correct Glyph Memory music path for local and production.
 *
 * Plain language for DEVS:
 * Local XAMPP often serves this game from /public/glyph/.
 * Production serves it from /glyph/.
 * The music file itself lives in public/sounds/music locally and /sounds/music live.
 */
function getGlyphMemoryMusicSrc() {
  if (window.location.pathname.startsWith('/public/')) {
    return '/public/sounds/music/glyph.mp3';
  }

  return '/sounds/music/glyph.mp3';
}

const glyphMemoryMusicController = window.NarrrfsGameMusicFactory({
  label: 'Glyph Memory',
  src: getGlyphMemoryMusicSrc(),
  volume: 0.18
});

/**
 * Return whether Glyph music is allowed by the global Narrrfs sound setting.
 *
 * Plain language for DEVS:
 * Glyph Memory has its own background music object because browser audio
 * permission works best when music starts directly from the Start button click.
 * This helper keeps that direct music object aligned with the global sound toggle.
 */
function isGlyphMusicAllowed() {
  if (window.NarrrfsAudio && typeof window.NarrrfsAudio.isMusicEnabled === 'function') {
    return window.NarrrfsAudio.isMusicEnabled();
  }

  if (window.NarrrfsSound && typeof window.NarrrfsSound.isEnabled === 'function') {
    return window.NarrrfsSound.isEnabled();
  }

  return safeLocalGet('narrrfs_music_enabled') !== 'false';
}

/**
 * Stop the direct Glyph background music safely.
 */
function stopGlyphDirectMusic(resetPosition = false) {
  if (!window.glyphMemoryDirectMusic) {
    return;
  }

  window.glyphMemoryDirectMusic.pause();

  if (resetPosition) {
    window.glyphMemoryDirectMusic.currentTime = 0;
  }
}

/**
 * Resume Glyph background music only when a run is active and sound is enabled.
 */
function resumeGlyphDirectMusic() {
  if (!window.glyphMemoryDirectMusic || !isGlyphMusicAllowed() || document.hidden) {
    return;
  }

  try {
    const playResult = window.glyphMemoryDirectMusic.play();
    if (playResult && typeof playResult.catch === 'function') {
      playResult.catch((error) => {
        console.warn('🎵 [GLYPH] Background music resume blocked:', error);
      });
    }
  } catch (error) {
    console.warn('🎵 [GLYPH] Background music resume blocked:', error);
  }
}

/**
 * Update the Glyph-native music button label and state.
 */
function updateGlyphMusicButtonState() {
  const button = document.getElementById('glyph-music-toggle');

  if (!button) {
    return;
  }

  const soundEnabled = isGlyphMusicAllowed();
  button.textContent = soundEnabled ? '🔊 Music ON' : '🔇 Music OFF';
  button.classList.toggle('glyph-music-toggle--off', !soundEnabled);
  button.setAttribute('aria-pressed', soundEnabled ? 'true' : 'false');
}

/**
 * Toggle global sound from the Glyph-native button and sync direct music.
 *
 * Plain language for DEVS:
 * The auth overlay sound button is generic. This button is for Glyph Memory and
 * controls both the global Narrrfs sound setting and the direct Glyph MP3 music.
 */
function toggleGlyphMusic() {
  const nextEnabled = !isGlyphMusicAllowed();

  if (window.NarrrfsAudio && typeof window.NarrrfsAudio.setMusicEnabled === 'function') {
    window.NarrrfsAudio.setMusicEnabled(nextEnabled);
  } else {
    safeLocalSet('narrrfs_sound_enabled', nextEnabled ? 'true' : 'false');
    window.dispatchEvent(new CustomEvent('narrrfs:sound-toggle', {
      detail: { enabled: nextEnabled }
    }));
  }

  if (nextEnabled) {
    resumeGlyphDirectMusic();
  } else {
    stopGlyphDirectMusic(false);
  }

  updateGlyphMusicButtonState();
}

/**
 * Keep Glyph direct music aligned when another global sound widget is clicked.
 */
function syncGlyphMusicFromGlobalToggle() {
  if (isGlyphMusicAllowed()) {
    resumeGlyphDirectMusic();
  } else {
    stopGlyphDirectMusic(false);
  }

  updateGlyphMusicButtonState();
}

window.addEventListener('storage', syncGlyphMusicFromGlobalToggle);
window.addEventListener('narrrfs:sound-toggle', syncGlyphMusicFromGlobalToggle);
window.addEventListener('narrrfs:music-toggle', syncGlyphMusicFromGlobalToggle);
document.addEventListener('visibilitychange', syncGlyphMusicFromGlobalToggle);

  // ------- DOM -------
  const bg = document.getElementById('bg');

  const menuView = document.getElementById('menuView');
  const gameView = document.getElementById('gameView');

  const difficultySelect = document.getElementById('difficultySelect');
  const startBtn = document.getElementById('startBtn');

  const boardEl = document.getElementById('board');
  const timerEl = document.getElementById('timer');
  const bestTimeEl = document.getElementById('bestTime');

  const menuBestTimeEl = document.getElementById('menuBestTime');

  const restartBtn = document.getElementById('restartBtn');
  const menuBtn = document.getElementById('menuBtn');

  // User display elements
  const userDisplayEl = document.getElementById('userDisplay');
  const userNameEl = document.getElementById('userName');
  const userAvatarEl = document.getElementById('userAvatar');
  const userBalanceEl = document.getElementById('userBalance');
  const loginMessageEl = document.getElementById('loginMessage');

  const winOverlay = document.getElementById('winOverlay');
  const finalTimeEl = document.getElementById('finalTime');
  const overlayBestTimeEl = document.getElementById('overlayBestTime');
  const newBestBadgeEl = document.getElementById('newBestBadge');
  const saveStatusEl = document.getElementById('saveStatus');
  const playAgainBtn = document.getElementById('playAgainBtn');
  const backToMenuBtn = document.getElementById('backToMenuBtn');

  // ------- STATE -------
  let activeDifficulty = 'easy';
  let activeSeasonKey = safeLocalGet('glyph_memory_active_season') || '';
  let deck = []; // { id, glyphSrc, matched }

  let firstPick = null;
  let secondPick = null;
  let lockBoard = false;

  let matchedPairs = 0;

  let timerStartPerf = 0;
  let timerInterval = null;
  let currentRunId = 0;
  let lastStartOrRestartAt = 0;
  let mismatchTimerId = null;
  let mismatchSettleUntil = 0;
  let winRevealTimerId = null;
  let isRoundFinished = false;

  function setSaveStatus(message, tone = 'info') {
    if (!saveStatusEl) return;
    if (!message) {
      saveStatusEl.textContent = '';
      saveStatusEl.hidden = true;
      saveStatusEl.className = 'saveStatus';
      return;
    }
    saveStatusEl.hidden = false;
    saveStatusEl.textContent = message;
    saveStatusEl.className = 'saveStatus' +
      (tone === 'warn' ? ' saveStatus--warn' : tone === 'muted' ? ' saveStatus--muted' : '');
  }

  // Zero-lag Web Audio API SFX engine with round-robin HTMLAudio fallback
  function createAudioPool(src, size = 3) {
    if (!src) return { pool: [], idx: 0 };
    const pool = Array.from({ length: size }, () => {
      const el = new Audio(src);
      el.preload = 'auto';
      return el;
    });
    return { pool, idx: 0 };
  }

  const audioPools = {
    match: createAudioPool(SOUNDS.match, 2),
    fail:  createAudioPool(SOUNDS.fail, 2),
    flip:  createAudioPool(SOUNDS.flip, 3),
  };

  let glyphAudioCtx = null;
  const glyphSfxBuffers = new Map();
  const glyphSfxLoading = new Map();

  function loadSfxBuffer(key, url) {
    if (!glyphAudioCtx || !url || glyphSfxBuffers.has(key) || glyphSfxLoading.has(key)) {
      return;
    }
    const promise = fetch(url)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.arrayBuffer();
      })
      .then((buf) => glyphAudioCtx.decodeAudioData(buf))
      .then((decoded) => {
        glyphSfxBuffers.set(key, decoded);
      })
      .catch((error) => {
        console.warn(`🔊 [GLYPH] WebAudio SFX decode skipped for ${key}:`, error);
      });
    glyphSfxLoading.set(key, promise);
  }

  function unlockGlyphAudio() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      if (!glyphAudioCtx) {
        glyphAudioCtx = new AudioCtx();
      }
      if (glyphAudioCtx.state === 'suspended' || glyphAudioCtx.state === 'interrupted') {
        const res = glyphAudioCtx.resume();
        if (res && typeof res.catch === 'function') {
          res.catch(() => {});
        }
      }
      Object.entries(SOUNDS).forEach(([key, url]) => loadSfxBuffer(key, url));
    } catch (_) {
      // Fall back cleanly to HTMLAudio pool.
    }
  }

  function playSound(key) {
    // Respect the shared Narrrfs SFX toggle.
    // DEVS FOR DECADES:
    // Glyph card flip/match/fail sounds are short SFX and must not depend on
    // the background music preference.
    if (window.NarrrfsAudio && typeof window.NarrrfsAudio.isSfxEnabled === 'function' && !window.NarrrfsAudio.isSfxEnabled()) {
      return;
    }

    if (!window.NarrrfsAudio && window.NarrrfsSound && !window.NarrrfsSound.isEnabled()) {
      return;
    }

    if (glyphAudioCtx && glyphSfxBuffers.has(key)) {
      try {
        if (glyphAudioCtx.state === 'suspended' || glyphAudioCtx.state === 'interrupted') {
          glyphAudioCtx.resume().catch(() => {});
        }
        const source = glyphAudioCtx.createBufferSource();
        source.buffer = glyphSfxBuffers.get(key);
        source.connect(glyphAudioCtx.destination);
        source.start(0);
        return;
      } catch (_) {
        // Fall through to HTMLAudio pool.
      }
    }

    const entry = audioPools[key];
    if (!entry || !entry.pool.length) {
      return;
    }
    const a = entry.pool[entry.idx % entry.pool.length];
    entry.idx = (entry.idx + 1) % entry.pool.length;

    try {
      a.currentTime = 0;
      const playRes = a.play();
      if (playRes && typeof playRes.catch === 'function') {
        playRes.catch(() => {});
      }
    } catch {
      // ignore
    }
  }

  
  // Cache for normalized glyph images (centers non-transparent pixels onto a square canvas)
  const glyphNormalizeCache = new Map(); // src -> Promise<string> (dataURL)

  // 🚨 IMPORTANT (January 2026):
  // Normalization replaces `img.src` with a `data:image/...` URL.
  // If the site uses a Content-Security-Policy that blocks `img-src data:`,
  // glyphs will appear missing in-game even though the original PNG URLs load fine.
  // Keep this OFF by default for maximum compatibility across local + production.
  const ENABLE_GLYPH_NORMALIZATION = false;

  function normalizeGlyphImage(src, size = 512, alphaThreshold = 8) {
    if (glyphNormalizeCache.has(src)) return glyphNormalizeCache.get(src);

    const p = new Promise((resolve) => {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.decoding = 'async';
      img.onload = () => {
        try {
          const w = img.naturalWidth || img.width;
          const h = img.naturalHeight || img.height;

          const tmp = document.createElement('canvas');
          tmp.width = w;
          tmp.height = h;
          const tctx = tmp.getContext('2d', { willReadFrequently: true });
          tctx.drawImage(img, 0, 0);

          const { data } = tctx.getImageData(0, 0, w, h);

          let minX = w, minY = h, maxX = -1, maxY = -1;
          for (let y = 0; y < h; y++) {
            for (let x = 0; x < w; x++) {
              const a = data[(y * w + x) * 4 + 3];
              if (a > alphaThreshold) {
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (x > maxX) maxX = x;
                if (y > maxY) maxY = y;
              }
            }
          }

          // If bbox not found, fall back to original
          if (maxX < minX || maxY < minY) {
            resolve(src);
            return;
          }

          const cropW = maxX - minX + 1;
          const cropH = maxY - minY + 1;

          const out = document.createElement('canvas');
          out.width = size;
          out.height = size;
          const octx = out.getContext('2d');

          const pad = Math.floor(size * 0.08); // 8% padding
          const avail = size - pad * 2;
          const scale = Math.min(avail / cropW, avail / cropH);
          const drawW = Math.round(cropW * scale);
          const drawH = Math.round(cropH * scale);
          const dx = Math.floor((size - drawW) / 2);
          const dy = Math.floor((size - drawH) / 2);

          octx.clearRect(0, 0, size, size);
          octx.drawImage(tmp, minX, minY, cropW, cropH, dx, dy, drawW, drawH);

          resolve(out.toDataURL('image/png'));
        } catch {
          resolve(src);
        }
      };
      img.onerror = () => resolve(src);
      img.src = src;
    });

    glyphNormalizeCache.set(src, p);
    return p;
  }

const glyphPreloadCache = new Map();

function preloadGlyphImages(sources, timeoutMs = 350) {
  if (!Array.isArray(sources) || sources.length === 0) return Promise.resolve();

  const pendingSources = [...new Set(sources)]
    .map((src) => getOptimizedGlyphPath(src))
    .filter((resolvedSrc) => {
      const cached = glyphPreloadCache.get(resolvedSrc);
      return !(cached && cached.complete && cached.naturalWidth > 0);
    });

  if (pendingSources.length === 0) {
    return Promise.resolve();
  }

  const preloadPromise = Promise.all(
    pendingSources.map((resolvedSrc) => {
      return new Promise((resolve) => {
        const img = new Image();
        img.decoding = 'async';
        img.onload = () => {
          glyphPreloadCache.set(resolvedSrc, img);
          resolve(resolvedSrc);
        };
        img.onerror = () => {
          console.warn('⚠️ [GLYPH] Preload failed:', resolvedSrc);
          resolve(resolvedSrc);
        };
        img.src = resolvedSrc;
        glyphPreloadCache.set(resolvedSrc, img);
      });
    })
  );

  return Promise.race([
    preloadPromise,
    new Promise((resolve) => {
      window.setTimeout(resolve, timeoutMs);
    }),
  ]);
}

/**
 * Creates a safe looping background music controller for Narrrfs games.
 *
 * Plain language for DEVS:
 * Browser audio cannot autoplay before a user action. This controller starts
 * only after game start / player interaction, follows the global Narrrfs sound
 * toggle, pauses on game pause, and stops when the run ends.
 */
window.NarrrfsGameMusicFactory = window.NarrrfsGameMusicFactory || function createNarrrfsGameMusicController(config) {
  let audio = null;
  let wantsPlayback = false;

  function isMusicAllowed() {
  if (window.NarrrfsAudio && typeof window.NarrrfsAudio.isMusicEnabled === 'function') {
    return window.NarrrfsAudio.isMusicEnabled();
  }

  if (window.NarrrfsSound && typeof window.NarrrfsSound.isEnabled === 'function') {
    return window.NarrrfsSound.isEnabled();
  }

  return safeLocalGet('narrrfs_music_enabled') !== 'false';
}

  function getAudio() {
    if (audio) {
      return audio;
    }

    audio = new Audio(config.src);
    audio.loop = true;
    audio.preload = 'auto';
    audio.volume = typeof config.volume === 'number' ? config.volume : 0.22;

    return audio;
  }

  async function start() {
    wantsPlayback = true;

    if (!isMusicAllowed() || document.hidden) {
      pause();
      return;
    }

    try {
      const music = getAudio();
      await music.play();
    } catch (error) {
      console.warn(`🎵 ${config.label} music could not start yet:`, error);
    }
  }

  function pause() {
    if (!audio) {
      return;
    }

    audio.pause();
  }

  function stop() {
    wantsPlayback = false;

    if (!audio) {
      return;
    }

    audio.pause();
    audio.currentTime = 0;
  }

  function sync() {
    if (!wantsPlayback) {
      return;
    }

    if (!isMusicAllowed() || document.hidden) {
      pause();
      return;
    }

    start();
  }

  window.addEventListener('storage', sync);
window.addEventListener('narrrfs:music-toggle', sync);
window.addEventListener('narrrfs:sound-toggle', sync);
document.addEventListener('visibilitychange', sync);

  return {
    start,
    pause,
    stop,
    sync
  };
};

// ------- HELPERS -------
  function setBackground(kind) {
    // kind: 'menu' | 'easy' | 'medium' | 'hard'
    const baseUrl = BACKGROUNDS[kind] || '';
    if (!baseUrl) {
      bg.style.backgroundImage = '';
      return;
    }

    // ✅ Use mobile-optimized background URL on phones
    const url = getOptimizedBackgroundPath(baseUrl);

    bg.style.backgroundImage = `url('${url}')`;
    bg.style.backgroundSize = 'cover';
    bg.style.backgroundPosition = 'center';
    bg.style.backgroundRepeat = 'no-repeat';
  }

  function showView(which) {
    const isMenu = which === 'menu';
    menuView.classList.toggle('view--active', isMenu);
    gameView.classList.toggle('view--active', !isMenu);

    if (isMenu) {
      setBackground('menu');
      window.NarrrfsMobileArcade?.unlock();
    } else {
      setBackground(activeDifficulty);
      window.NarrrfsMobileArcade?.lock({
        stageEl: gameView,
        gameKey: 'glyph'
      });
    }
  }

  function shuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  function pickRandomGlyphs(count) {
    // CRITICAL: Verify GLYPH_FILES has no duplicates
    const uniqueGlyphFiles = [...new Set(GLYPH_FILES)];
    if (uniqueGlyphFiles.length !== GLYPH_FILES.length) {
      console.warn(`⚠️ [GLYPH] GLYPH_FILES array contains duplicates! Total: ${GLYPH_FILES.length}, Unique: ${uniqueGlyphFiles.length}`);
      // Use unique glyphs only
      GLYPH_FILES.splice(0, GLYPH_FILES.length, ...uniqueGlyphFiles);
    }
    
    if (GLYPH_FILES.length < count) {
      console.error(`❌ [GLYPH] Not enough glyphs: need ${count}, have ${GLYPH_FILES.length}`);
      // Fallback: return what we have and pad with repeats if necessary (shouldn't happen with 36 glyphs)
      const result = [...GLYPH_FILES];
      while (result.length < count) {
        result.push(GLYPH_FILES[Math.floor(Math.random() * GLYPH_FILES.length)]);
      }
      return result.slice(0, count);
    }
    
    // CRITICAL: Shuffle entire array first, then take first N to ensure uniqueness
    const shuffled = shuffle([...GLYPH_FILES]);
    const selected = shuffled.slice(0, count);
    
    // CRITICAL: Verify selected glyphs are all unique
    const selectedSet = new Set(selected);
    if (selectedSet.size !== selected.length) {
      console.error(`❌ [GLYPH] Duplicate glyphs selected! Requested: ${count}, Unique: ${selectedSet.size}`);
      // Fix: Remove duplicates and add more unique glyphs
      const fixed = Array.from(selectedSet);
      const available = shuffled.slice(count);
      while (fixed.length < count && available.length > 0) {
        const next = available.shift();
        if (!fixed.includes(next)) {
          fixed.push(next);
        }
      }
      console.warn(`🔧 [GLYPH] Fixed duplicate selection - using ${fixed.length} unique glyphs`);
      return fixed;
    }
    
    return selected;
  }

  function formatTime(ms) {
    const totalMs = Math.max(0, Math.round(Number(ms)));
    const totalSec = Math.floor(totalMs / 1000);
    const min = Math.floor(totalSec / 60);
    const sec = totalSec % 60;
    const milliseconds = totalMs % 1000;
    return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}.${String(milliseconds).padStart(3, '0')}`;
  }

  // ------- BEST TIME (per difficulty, season-scoped) -------
  function bestKey(diff) {
    return activeSeasonKey
      ? `glyphMemoryBestTime:${activeSeasonKey}:${diff}`
      : `glyphMemoryBestTime:${diff}`;
  }

  function getBestTime(diff) {
    const raw = safeLocalGet(bestKey(diff));
    const n = raw ? Number(raw) : NaN;
    return Number.isFinite(n) && n > 0 ? n : null;
  }

  function setBestTime(diff, ms) {
    const rounded = Math.max(1, Math.round(Number(ms) || 0));
    safeLocalSet(bestKey(diff), String(rounded));
  }

  function updateBestTimeUI(diff) {
    const best = getBestTime(diff);
    const label = best == null ? '—' : formatTime(best);
    if (bestTimeEl) bestTimeEl.textContent = label;
    if (menuBestTimeEl) menuBestTimeEl.textContent = label;
    if (overlayBestTimeEl) overlayBestTimeEl.textContent = label;
  }

  function clearTurnTimeouts() {
    if (mismatchTimerId) {
      window.clearTimeout(mismatchTimerId);
      mismatchTimerId = null;
    }
    if (winRevealTimerId) {
      window.clearTimeout(winRevealTimerId);
      winRevealTimerId = null;
    }
  }

  function getElapsedTimerMs() {
    if (timerStartPerf <= 0) {
      return 0;
    }
    return Math.max(1, Math.round(performance.now() - timerStartPerf));
  }

  function startTimer(runId = currentRunId) {
    stopTimer();
    if (timerEl) {
      timerEl.textContent = '00:00.000';
    }
    const schedule = typeof window.requestAnimationFrame === 'function'
      ? window.requestAnimationFrame.bind(window)
      : (cb) => window.setTimeout(cb, 0);

    schedule(() => {
      if (runId !== currentRunId || isRoundFinished) {
        return;
      }
      timerStartPerf = performance.now();
      timerInterval = window.setInterval(() => {
        if (runId !== currentRunId || isRoundFinished) {
          stopTimer();
          return;
        }
        if (timerEl) {
          timerEl.textContent = formatTime(getElapsedTimerMs());
        }
      }, 47);
    });
  }

  function stopTimer(finalMs = null) {
    if (timerInterval) {
      window.clearInterval(timerInterval);
      timerInterval = null;
    }
    timerStartPerf = 0;
    if (finalMs != null && timerEl) {
      timerEl.textContent = formatTime(finalMs);
    }
  }

  function resetTurnPicks() {
    clearTurnTimeouts();
    firstPick = null;
    secondPick = null;
    lockBoard = false;
    mismatchSettleUntil = 0;
  }

  // ------- BUILD DECK + BOARD -------
  function buildDeck(difficultyKey) {
    const cfg = DIFFICULTIES[difficultyKey];
    const glyphs = pickRandomGlyphs(cfg.pairs);

    // CRITICAL: Ensure we have exactly the right number of unique glyphs
    if (glyphs.length !== cfg.pairs) {
      console.error(`❌ [GLYPH] Incorrect glyph count: expected ${cfg.pairs}, got ${glyphs.length}`);
    }

    // CRITICAL: Verify all glyphs are unique (no duplicates)
    const uniqueGlyphs = [...new Set(glyphs)];
    if (uniqueGlyphs.length !== glyphs.length) {
      console.error(`❌ [GLYPH] Duplicate glyphs detected! Expected ${glyphs.length} unique, got ${uniqueGlyphs.length}`);
      // Fix: Use only unique glyphs and pad if needed
      const fixedGlyphs = uniqueGlyphs.slice(0, cfg.pairs);
      while (fixedGlyphs.length < cfg.pairs) {
        // Add random glyphs that aren't already selected
        const available = GLYPH_FILES.filter(g => !fixedGlyphs.includes(g));
        if (available.length > 0) {
          fixedGlyphs.push(available[Math.floor(Math.random() * available.length)]);
        } else {
          // Fallback: duplicate a glyph if we run out (shouldn't happen)
          fixedGlyphs.push(GLYPH_FILES[Math.floor(Math.random() * GLYPH_FILES.length)]);
        }
      }
      console.warn(`🔧 [GLYPH] Fixed duplicate glyphs - using ${fixedGlyphs.length} unique glyphs`);
      glyphs.splice(0, glyphs.length, ...fixedGlyphs);
    }

    // Create exactly 2 cards per glyph (pair matching)
    const cards = glyphs.flatMap((src, idx) => {
      // CRITICAL: Both cards MUST have the exact same glyphSrc for matching to work
      const cardA = { id: `${idx}-a`, glyphSrc: src, matched: false };
      const cardB = { id: `${idx}-b`, glyphSrc: src, matched: false }; // Same src as cardA!
      
      // Validation: Verify both cards have identical glyphSrc
      if (cardA.glyphSrc !== cardB.glyphSrc) {
        console.error(`❌ [GLYPH] Pair mismatch detected! Card A: ${cardA.glyphSrc}, Card B: ${cardB.glyphSrc}`);
      }
      
      return [cardA, cardB];
    });

    // CRITICAL: Verify deck integrity before shuffling
    // Each glyph should appear exactly twice
    const glyphCounts = {};
    cards.forEach(card => {
      glyphCounts[card.glyphSrc] = (glyphCounts[card.glyphSrc] || 0) + 1;
    });
    
    // Validate: All glyphs should appear exactly 2 times
    const invalidGlyphs = Object.entries(glyphCounts).filter(([src, count]) => count !== 2);
    if (invalidGlyphs.length > 0) {
      console.error(`❌ [GLYPH] Deck validation failed! Glyphs with incorrect counts:`, invalidGlyphs);
      invalidGlyphs.forEach(([src, count]) => {
        console.error(`  - ${src}: appears ${count} times (should be 2)`);
      });
    } else {
      console.log(`✅ [GLYPH] Deck validated: ${Object.keys(glyphCounts).length} unique glyphs, each appearing exactly 2 times`);
    }

    // Verify total card count matches expected
    const expectedCards = cfg.pairs * 2;
    if (cards.length !== expectedCards) {
      console.error(`❌ [GLYPH] Incorrect card count: expected ${expectedCards}, got ${cards.length}`);
    }

    const shuffled = shuffle(cards);
    
    // Final validation after shuffle
    const shuffledGlyphCounts = {};
    shuffled.forEach(card => {
      shuffledGlyphCounts[card.glyphSrc] = (shuffledGlyphCounts[card.glyphSrc] || 0) + 1;
    });
    const invalidAfterShuffle = Object.entries(shuffledGlyphCounts).filter(([src, count]) => count !== 2);
    if (invalidAfterShuffle.length > 0) {
      console.error(`❌ [GLYPH] Deck corrupted after shuffle!`, invalidAfterShuffle);
    }
    
    return shuffled;
  }

  function setBoardGrid(difficultyKey) {
    const cfg = DIFFICULTIES[difficultyKey] || DIFFICULTIES.easy;
    const totalCards = cfg.pairs * 2;
    const cols = (window.innerWidth <= 560 && difficultyKey === 'hard') ? 4 : cfg.cols;
    const rows = Math.ceil(totalCards / cols);

    boardEl.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;

    if (window.innerWidth <= 768) {
      boardEl.style.gridTemplateRows = `repeat(${rows}, minmax(0, 1fr))`;
      boardEl.style.gridAutoRows = '';
    } else {
      boardEl.style.gridTemplateRows = '';
      // Card size: scale up to use the available empty space, while staying responsive
      const usableWidth = Math.min(1400, Math.floor(window.innerWidth * 0.96));
      const gutter = 22; // matches CSS gap
      const perCol = Math.floor((usableWidth - gutter * (cols - 1)) / cols);
      // Clamp so easy/medium can be larger, hard stays sensible
      const maxCard = Math.max(140, Math.min(240, perCol));
      boardEl.style.gridAutoRows = `${maxCard}px`;
    }

    boardEl.setAttribute('aria-label', `Card grid ${rows} by ${cols}`);
  }

  window.addEventListener('resize', () => {
    if (gameView && gameView.classList.contains('view--active')) {
      setBoardGrid(activeDifficulty);
    }
  });

  function bindCardTap(el, onSelect) {
    if (!el || typeof onSelect !== 'function') return;

    if (typeof window.PointerEvent === 'function') {
      el.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        if (e.pointerType !== 'mouse' && e.cancelable) {
          e.preventDefault();
        }
        el._lastPointerDownAt = performance.now();
        onSelect();
      });
    } else {
      el.addEventListener('touchstart', (e) => {
        if (e.cancelable) {
          e.preventDefault();
        }
        el._lastPointerDownAt = performance.now();
        onSelect();
      }, { passive: false });
    }

    el.addEventListener('click', (e) => {
      if (e.detail === 0 || (performance.now() - (el._lastPointerDownAt || 0)) > 450) {
        onSelect();
        return;
      }
      e.preventDefault();
    });
  }

  function renderBoard() {
    boardEl.innerHTML = '';
    matchedPairs = 0;

    const frag = document.createDocumentFragment();

    deck.forEach((card, index) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'card card--deal';
      btn.dataset.index = String(index);
      btn.setAttribute('role', 'gridcell');
      btn.setAttribute('aria-label', 'Hidden card');
      btn.addEventListener('animationend', () => {
        btn.classList.remove('card--deal');
      }, { once: true });

      const back = document.createElement('div');
      back.className = 'cardInner cardBack';
      back.innerHTML = '<div class="backIcon" aria-hidden="true"></div><div class="backDots" aria-hidden="true"></div>';

      const front = document.createElement('div');
      front.className = 'cardInner cardFront';

      const img = document.createElement('img');
      img.alt = 'Glyph';
      // Use eager loading so glyphs decode cleanly once attached to visible DOM
      img.loading = 'eager';
      img.decoding = 'async';
      const showMissingGlyphFallback = () => {
        if (front.classList.contains('missing')) return;
        front.textContent = 'MISSING';
        front.classList.add('missing');
      };
      const optimizedSrc = getOptimizedGlyphPath(card.glyphSrc);
      // Fall back cleanly from glyphs-sm to original full-size PNG before showing MISSING.
      img.addEventListener('error', () => {
        if (!img.dataset.triedOriginal && optimizedSrc !== card.glyphSrc) {
          img.dataset.triedOriginal = '1';
          img.src = card.glyphSrc;
          return;
        }
        console.warn('Missing glyph file:', card.glyphSrc);
        showMissingGlyphFallback();
      });
      img.src = optimizedSrc;
      img.draggable = false;
      // Optional: Auto-center glyph pixels so they look consistent at all card sizes.
      // Disabled by default because it uses data: URLs which may be blocked by CSP.
      if (ENABLE_GLYPH_NORMALIZATION) {
        normalizeGlyphImage(optimizedSrc).then((dataUrl) => {
          if (!front.classList.contains('missing')) img.src = dataUrl;
        });
      }

      front.appendChild(img);
      btn.appendChild(back);
      btn.appendChild(front);

      bindCardTap(btn, () => handleCardSelect(btn));

      frag.appendChild(btn);
    });

    boardEl.appendChild(frag);
  }

  // ------- DECK VERIFICATION -------
  function verifyDeckIntegrity() {
    const glyphCounts = {};
    deck.forEach(card => {
      glyphCounts[card.glyphSrc] = (glyphCounts[card.glyphSrc] || 0) + 1;
    });
    
    const invalid = Object.entries(glyphCounts).filter(([src, count]) => count !== 2);
    if (invalid.length > 0) {
      console.error(`❌ [GLYPH] Deck integrity check FAILED:`, invalid);
      return false;
    }
    return true;
  }

  // ------- GAMEPLAY -------
  function handleCardSelect(cardBtn) {
    unlockGlyphAudio();
    if (lockBoard || isRoundFinished || !cardBtn) return;

    // Fast-cancel any open mismatch once the brief 140ms anti-bounce window passes
    if (firstPick && secondPick) {
      if (performance.now() < mismatchSettleUntil) {
        return;
      }
      if (cardBtn === firstPick.el || cardBtn === secondPick.el) {
        return;
      }
      clearTurnTimeouts();
      flipCardDown(firstPick.el);
      flipCardDown(secondPick.el);
      firstPick = null;
      secondPick = null;
      mismatchSettleUntil = 0;
    }

    const index = Number(cardBtn.dataset.index);
    const card = deck[index];

    if (!card) {
      console.error(`❌ [GLYPH] Card at index ${index} not found in deck!`);
      return;
    }

    if (card.matched) return;
    if (cardBtn.classList.contains('is-flipped')) return;

    if (timerStartPerf <= 0) {
      timerStartPerf = performance.now();
    }

    flipCardUp(cardBtn);

    if (!firstPick) {
      firstPick = { index, el: cardBtn };
      return;
    }

    if (firstPick.index === index) return;

    secondPick = { index, el: cardBtn };

    const firstCard = deck[firstPick.index];
    const secondCard = deck[secondPick.index];

    // CRITICAL: Verify both cards exist
    if (!firstCard || !secondCard) {
      console.error(`❌ [GLYPH] Card mismatch - firstCard:`, firstCard, `secondCard:`, secondCard);
      resetTurnPicks();
      return;
    }

    // CRITICAL: Use strict comparison and verify glyphSrc values
    const firstSrc = String(firstCard.glyphSrc || '').trim();
    const secondSrc = String(secondCard.glyphSrc || '').trim();

    if (!firstSrc || !secondSrc) {
      console.error(`❌ [GLYPH] Empty glyphSrc detected! First: "${firstSrc}", Second: "${secondSrc}"`);
      playSound('fail');
      mismatchSettleUntil = performance.now() + 140;
      const runId = currentRunId;
      const pickA = firstPick.el;
      const pickB = secondPick.el;
      mismatchTimerId = window.setTimeout(() => {
        mismatchTimerId = null;
        if (runId !== currentRunId) return;
        flipCardDown(pickA);
        flipCardDown(pickB);
        firstPick = null;
        secondPick = null;
        mismatchSettleUntil = 0;
      }, 650);
      return;
    }

    const isMatch = firstSrc === secondSrc;

    // Diagnostic logging for mismatches (especially near the end)
    if (!isMatch) {
      const remainingUnmatched = deck.filter(c => !c.matched);
      if (remainingUnmatched.length <= 4) {
        console.warn(`⚠️ [GLYPH] Mismatch detected with ${remainingUnmatched.length} cards remaining:`, {
          first: firstSrc,
          second: secondSrc,
          remaining: remainingUnmatched.map(c => c.glyphSrc)
        });
        // Verify deck integrity when near the end
        if (!verifyDeckIntegrity()) {
          console.error(`❌ [GLYPH] Deck corruption detected during gameplay!`);
        }
      }
    }

    if (isMatch) {
      firstCard.matched = true;
      secondCard.matched = true;

      firstPick.el.setAttribute('aria-disabled', 'true');
      secondPick.el.setAttribute('aria-disabled', 'true');
      firstPick.el.setAttribute('aria-label', 'Matched card');
      secondPick.el.setAttribute('aria-label', 'Matched card');

      matchedPairs += 1;
      firstPick = null;
      secondPick = null;
      lockBoard = false;

      if (matchedPairs >= DIFFICULTIES[activeDifficulty].pairs) {
        const exactElapsedMs = getElapsedTimerMs();
        stopTimer(exactElapsedMs);
        isRoundFinished = true;
        lockBoard = true;
        playSound('match');
        completeWin(exactElapsedMs, currentRunId);
        return;
      }

      playSound('match');
    } else {
      playSound('fail');
      mismatchSettleUntil = performance.now() + 140;
      const runId = currentRunId;
      const pickA = firstPick.el;
      const pickB = secondPick.el;

      mismatchTimerId = window.setTimeout(() => {
        mismatchTimerId = null;
        if (runId !== currentRunId) return;
        flipCardDown(pickA);
        flipCardDown(pickB);
        firstPick = null;
        secondPick = null;
        mismatchSettleUntil = 0;
      }, 650);
    }
  }

  function flipCardUp(el) {
    el.classList.remove('card--deal');
    el.classList.add('is-flipped');
    el.setAttribute('aria-label', 'Revealed card');
    // Play flip sound when card is revealed
    playSound('flip');

    // Force-hide the back face to avoid any CSS stacking regressions
    // (Some theming edits added additional `.cardBack` rules that can override flip visuals.)
    const back = el.querySelector('.cardBack');
    if (back) back.style.display = 'none';
    
    // Debug help (January 2026): verify glyph image actually loaded
    // This logs only ~5% of flips to avoid console spam.
    if (Math.random() < 0.05) {
      const img = el.querySelector('.cardFront img');
      if (img) {
        // Delay a tick so layout/image state is updated
        window.setTimeout(() => {
          console.log('🧩 [GLYPH] Flip debug:', {
            src: img.currentSrc || img.src,
            complete: img.complete,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
          });
        }, 0);
      }
    }
  }

  function flipCardDown(el) {
    el.classList.remove('is-flipped');
    el.setAttribute('aria-label', 'Hidden card');

    // Restore back face (paired with flipCardUp override above)
    const back = el.querySelector('.cardBack');
    if (back) back.style.display = '';
  }

  function completeWin(elapsedMs, runId) {
    if (runId !== currentRunId) {
      return;
    }

    const totalPairs = DIFFICULTIES[activeDifficulty].pairs;

    // CRITICAL: Diagnostic check before win - verify all pairs are matched correctly
    const unmatchedCards = deck.filter(card => !card.matched);
    if (unmatchedCards.length > 0) {
      console.warn(`⚠️ [GLYPH] Win check: ${unmatchedCards.length} unmatched card(s) remaining:`, unmatchedCards.map(c => c.glyphSrc));

      // CRITICAL: Check if remaining unmatched cards can form pairs
      const remainingGlyphCounts = {};
      unmatchedCards.forEach(card => {
        remainingGlyphCounts[card.glyphSrc] = (remainingGlyphCounts[card.glyphSrc] || 0) + 1;
      });

      const unmatchedPairs = Object.entries(remainingGlyphCounts).filter(([src, count]) => count !== 2);
      if (unmatchedPairs.length > 0) {
        console.error(`❌ [GLYPH] CRITICAL BUG: Unmatched cards cannot form pairs!`, unmatchedPairs);
        unmatchedPairs.forEach(([src, count]) => {
          console.error(`  - Glyph "${src}": appears ${count} time(s) (should be 2 for matching)`);
        });

        // Prevent win if deck is corrupted
        console.error(`❌ [GLYPH] Blocking win - deck corruption detected!`);
        isRoundFinished = false;
        lockBoard = false;
        return; // Don't allow win with corrupted deck
      }
    }

    if (matchedPairs >= totalPairs) {
      // CRITICAL: Final validation - verify all cards are actually matched
      const stillUnmatched = deck.filter(card => !card.matched);
      if (stillUnmatched.length > 0) {
        console.error(`❌ [GLYPH] Win condition met but ${stillUnmatched.length} card(s) still unmatched!`, stillUnmatched);
        isRoundFinished = false;
        lockBoard = false;
        return;
      }

      stopGlyphDirectMusic(true);
      glyphMemoryMusicController.stop();

      const safeElapsedMs = Math.max(1, Math.round(Number(elapsedMs) || 0));
      finalTimeEl.textContent = formatTime(safeElapsedMs);

      // Best time logic (per difficulty, season-scoped)
      const prevBest = getBestTime(activeDifficulty);
      const isNewBest = prevBest == null || safeElapsedMs < prevBest;
      if (isNewBest) setBestTime(activeDifficulty, safeElapsedMs);
      updateBestTimeUI(activeDifficulty);

      setSaveStatus('⏳ Saving time to leaderboard…', 'muted');
      void saveGlyphScore(activeDifficulty, safeElapsedMs, totalPairs, runId);

      if (newBestBadgeEl) newBestBadgeEl.hidden = !isNewBest;

      console.log(`✅ [GLYPH] Game won! Time: ${formatTime(safeElapsedMs)}, Pairs matched: ${matchedPairs}/${totalPairs}`);
      window.NarrrfsMobileArcade?.unlock();
      winRevealTimerId = window.setTimeout(() => {
        winRevealTimerId = null;
        if (runId !== currentRunId) return;
        winOverlay.hidden = false;
      }, 160);
    }
  }

  /**
   * Resolve the logged-in Discord player for score saving.
   *
   * Plain language for DEVS:
   * Glyph Memory local best times are browser-only. The leaderboard needs a
   * real Discord ID. Mobile browsers can lose localStorage while still keeping
   * sessionStorage or the shared Narrrfs page globals, so check all known safe
   * places before deciding the score is local-only.
   */
  function resolveGlyphScorePlayer() {
    const discordId = String(
      window.sessionDiscordId ||
      safeLocalGet('discord_id') ||
      safeSessionGet('discord_id') ||
      safeLocalGet('narrrfs_last_discord_id') ||
      ''
    ).trim();

    const discordName = String(
      window.sessionDiscordUsername ||
      safeLocalGet('discord_name') ||
      safeSessionGet('discord_name') ||
      safeLocalGet('narrrfs_last_discord_name') ||
      safeLocalGet('DISCORD_NAME') ||
      'Player'
    ).trim();

    if (discordId) {
      safeLocalSet('discord_id', discordId);
      safeSessionSet('discord_id', discordId);
    }

    if (discordName && discordName !== 'Player') {
      safeLocalSet('discord_name', discordName);
      safeSessionSet('discord_name', discordName);
    }

    return { discordId, discordName };
  }

  // 🧩 Save Glyph Memory score to database (if Discord logged in)
  async function saveGlyphScore(difficulty, timeMs, pairsMatched, runId = currentRunId) {
    const normalizedDifficulty = String(difficulty || '').trim().toLowerCase();
    const allowedDifficulties = ['easy', 'medium', 'hard'];
    const integerTimeMs = Math.max(1, Math.round(Number(timeMs) || 0));

    if (!allowedDifficulties.includes(normalizedDifficulty)) {
      console.warn('⚠️ Glyph score not saved: invalid difficulty payload', {
        difficulty,
        normalizedDifficulty,
        timeMs: integerTimeMs,
        pairsMatched
      });
      if (runId === currentRunId) {
        setSaveStatus('⚠️ Could not reach leaderboard — saved locally', 'warn');
      }
      return;
    }

    const { discordId, discordName } = resolveGlyphScorePlayer();

    if (!discordId) {
      // Not logged in - use localStorage only (existing behavior)
      console.warn('🧩 Glyph score stayed local only: no Discord ID found for backend save', {
        difficulty: normalizedDifficulty,
        time_ms: integerTimeMs,
        pairs_matched: pairsMatched
      });
      if (runId === currentRunId) {
        setSaveStatus('ℹ️ Guest run — best saved on this device', 'muted');
      }
      return;
    }

    const scorePayload = {
      game: 'glyph_memory',
      discord_id: discordId,
      discord_name: discordName || 'Player',
      difficulty: normalizedDifficulty,
      time_ms: integerTimeMs,
      pairs_matched: pairsMatched,
      wallet: '' // Not required for glyph memory
    };

    console.log('🧩 Saving Glyph Memory score payload:', scorePayload);

    try {
      const response = await fetch('/api/dev/save-score.php', {
        method: 'POST',
        credentials: 'include',
        cache: 'no-store',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(scorePayload)
      });

      const data = await response.json();

      if (response.ok && data.success) {
        console.log('✅ Glyph Memory score saved to database:', data.message || data);
        if (runId === currentRunId) {
          setSaveStatus('✅ Saved to Season Leaderboard', 'info');
        }
        setActiveLeaderboardDifficulty(normalizedDifficulty);
        fetchLeaderboard();
        return;
      }

      console.warn('⚠️ Failed to save Glyph Memory score:', {
        status: response.status,
        response: data,
        payload: scorePayload
      });
      if (runId === currentRunId) {
        setSaveStatus('⚠️ Could not reach leaderboard — saved locally', 'warn');
      }
    } catch (error) {
      console.error('❌ Error saving Glyph Memory score:', {
        error,
        payload: scorePayload
      });
      if (runId === currentRunId) {
        setSaveStatus('⚠️ Could not reach leaderboard — saved locally', 'warn');
      }
      // LocalStorage fallback already saved the best time.
    }
  }

  // ------- FLOW CONTROLS -------
  async function launchRun(difficultyKey, isRestart = false) {
    const now = performance.now();
    if (now - lastStartOrRestartAt < 180) {
      return;
    }
    lastStartOrRestartAt = now;

    const runId = ++currentRunId;
    const normalizedDifficulty = DIFFICULTIES[difficultyKey] ? difficultyKey : 'easy';
    activeDifficulty = normalizedDifficulty;

    if (!isRestart && startBtn) {
      startBtn.disabled = true;
      startBtn.textContent = 'Starting…';
    }

    try {
      stopTimer();
      clearTurnTimeouts();
      isRoundFinished = false;
      winOverlay.hidden = true;
      setSaveStatus('', 'muted');
      resetTurnPicks();

      deck = buildDeck(activeDifficulty);

      // CRITICAL: Verify deck integrity before starting game
      if (!verifyDeckIntegrity()) {
        console.error(`❌ [GLYPH] Cannot start game - deck is corrupted!`);
        alert('⚠️ Error: Game deck is invalid. Please try restarting the game.');
        if (isRestart) goToMenu();
        return;
      }

      // CRITICAL: Verify deck has correct number of cards
      const expectedCards = DIFFICULTIES[activeDifficulty].pairs * 2;
      if (deck.length !== expectedCards) {
        console.error(`❌ [GLYPH] Deck has wrong number of cards: expected ${expectedCards}, got ${deck.length}`);
        alert('⚠️ Error: Game deck has incorrect number of cards. Please try restarting the game.');
        if (isRestart) goToMenu();
        return;
      }

      // CRITICAL: Verify all glyphs appear exactly twice
      const glyphCounts = {};
      deck.forEach(card => {
        glyphCounts[card.glyphSrc] = (glyphCounts[card.glyphSrc] || 0) + 1;
      });
      const invalidPairs = Object.entries(glyphCounts).filter(([src, count]) => count !== 2);
      if (invalidPairs.length > 0) {
        console.error(`❌ [GLYPH] Deck validation failed - glyphs with incorrect counts:`, invalidPairs);
        alert('⚠️ Error: Game deck validation failed. Please try restarting the game.');
        if (isRestart) goToMenu();
        return;
      }

      console.log(`✅ [GLYPH] Deck validated (${activeDifficulty}, run #${runId}): ${deck.length} cards, ${Object.keys(glyphCounts).length} unique glyphs`);

      updateBestTimeUI(activeDifficulty);
      if (newBestBadgeEl) newBestBadgeEl.hidden = true;

      showView('game');
      setBoardGrid(activeDifficulty);
      renderBoard();
      if (timerEl) timerEl.textContent = '00:00.000';

      if (isRestart) {
        resumeGlyphDirectMusic();
        glyphMemoryMusicController.start();
        window.NarrrfsMobileArcade?.lock({ stageEl: gameView, gameKey: 'glyph' });
      }

      const deckSources = deck.map(card => card.glyphSrc);
      lockBoard = true;
      await preloadGlyphImages(deckSources, isRestart ? 250 : 350);
      if (runId !== currentRunId || !gameView.classList.contains('view--active')) {
        return;
      }
      lockBoard = false;
      startTimer(runId);
    } catch (error) {
      console.error('❌ [GLYPH] Failed to launch run:', error);
      if (runId === currentRunId) {
        lockBoard = false;
        alert('⚠️ Could not start game. Please try again.');
        goToMenu();
      }
    } finally {
      if (startBtn) {
        startBtn.disabled = false;
        startBtn.textContent = 'Start Game';
      }
    }
  }

  function startGame() {
    const selectedDiff = (difficultySelect && difficultySelect.value) || 'easy';
    return launchRun(selectedDiff, false);
  }

  function restartGame() {
    unlockGlyphAudio();
    return launchRun(activeDifficulty, true);
  }

function goToMenu() {
  currentRunId += 1;
  isRoundFinished = false;
  stopTimer();

  stopGlyphDirectMusic(true);
  glyphMemoryMusicController.stop();
  winOverlay.hidden = true;
  setSaveStatus('', 'muted');
  resetTurnPicks();

  if (startBtn) {
    startBtn.disabled = false;
    startBtn.textContent = 'Start Game';
  }

  if (newBestBadgeEl) {
    newBestBadgeEl.hidden = true;
  }

  showView('menu');
  updateBestTimeUI((difficultySelect && difficultySelect.value) || activeDifficulty);
}


// ------- EVENTS -------

/**
 * Starts Glyph Memory from the real Start button click.
 *
 * Plain language for DEVS:
 * Browsers only allow music when play() is directly connected to a user action.
 * This creates/reuses one direct looping MP3 object and lets the shared Narrrfs
 * sound button decide whether it should play.
 */
function startGlyphMemoryRunFromButton(event) {
  if (event && event.cancelable) {
    event.preventDefault();
  }

  unlockGlyphAudio();

  try {
    console.log('🎵 [GLYPH] Start button clicked. Music src:', getGlyphMemoryMusicSrc());

    if (!window.glyphMemoryDirectMusic) {
      const music = new Audio(getGlyphMemoryMusicSrc());
      music.loop = true;
      music.preload = 'auto';
      music.volume = 0.18;

      window.glyphMemoryDirectMusic = music;
    }

    if (isGlyphMusicAllowed()) {
      const playResult = window.glyphMemoryDirectMusic.play();
      if (playResult && typeof playResult.then === 'function') {
        playResult
          .then(() => {
            console.log('✅ [GLYPH] Background music started.');
          })
          .catch((error) => {
            console.warn('🎵 [GLYPH] Background music could not start:', error);
          });
      }
    } else {
      console.log('🔇 [GLYPH] Music is disabled by shared Narrrfs sound toggle.');
      stopGlyphDirectMusic(false);
    }
  } catch (audioError) {
    console.warn('🎵 [GLYPH] Audio startup error ignored so gameplay can start:', audioError);
  }

  void startGame();
}

function bindFastTap(el, handler) {
  if (!el || typeof handler !== 'function') return;

  let touchStartX = 0;
  let touchStartY = 0;
  let touchMoved = false;
  let lastTouchTapAt = 0;

  el.addEventListener('touchstart', (e) => {
    const touch = e.touches && e.touches[0];
    if (!touch) return;
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    touchMoved = false;
  }, { passive: true });

  el.addEventListener('touchmove', (e) => {
    const touch = e.touches && e.touches[0];
    if (!touch) return;
    if (Math.abs(touch.clientX - touchStartX) > 12 || Math.abs(touch.clientY - touchStartY) > 12) {
      touchMoved = true;
    }
  }, { passive: true });

  el.addEventListener('touchend', (e) => {
    if (touchMoved || el.disabled) return;
    if (e.cancelable) {
      e.preventDefault();
    }
    lastTouchTapAt = Date.now();
    handler(e);
  }, { passive: false });

  el.addEventListener('click', (e) => {
    if (Date.now() - lastTouchTapAt < 450) {
      e.preventDefault();
      return;
    }
    handler(e);
  });
}

bindFastTap(startBtn, startGlyphMemoryRunFromButton);
  difficultySelect.addEventListener('change', () => {
    // Update the menu best-time preview and leaderboard tab for the selected difficulty
    updateBestTimeUI(difficultySelect.value);
    setActiveLeaderboardDifficulty(difficultySelect.value);
  });
  bindFastTap(restartBtn, restartGame);
  bindFastTap(menuBtn, goToMenu);

  bindFastTap(playAgainBtn, restartGame);
  bindFastTap(backToMenuBtn, goToMenu);

  // Prevent any drag previews in-game
  document.addEventListener('dragstart', (ev) => {
    if (ev.target && (ev.target.tagName === 'IMG' || ev.target.closest?.('.card'))) {
      ev.preventDefault();
    }
  });

/**
 * Injects a Glyph-themed music button and hides the generic auth sound widget.
 *
 * Plain language for DEVS:
 * cheese-auth-indicator.js is still used for login display, but its small
 * generic sound button does not fit Glyph Memory. This adds a local themed
 * music button without changing the shared auth script for other pages.
 */
function setupGlyphMusicControls() {
  const existingButton = document.getElementById('glyph-music-toggle');

  if (document.getElementById('narrrfs-audio-controls') || window.NarrrfsAudio) {
    if (existingButton) {
      existingButton.remove();
    }
    return;
  }

  if (existingButton) {
    updateGlyphMusicButtonState();
    return;
  }

  const button = document.createElement('button');
  button.id = 'glyph-music-toggle';
  button.type = 'button';
  button.className = 'glyph-music-toggle';
  button.addEventListener('click', toggleGlyphMusic);

  document.body.appendChild(button);
  updateGlyphMusicButtonState();

  const hideGenericSoundButton = () => {
    document.querySelectorAll('button, a, div').forEach((element) => {
      const text = (element.textContent || '').trim();

      if (text === '🔊 Sound ON' || text === '🔇 Sound OFF' || text === 'Sound ON' || text === 'Sound OFF') {
        element.classList.add('glyph-hide-generic-sound-control');
      }
    });
  };

  hideGenericSoundButton();
  setTimeout(hideGenericSoundButton, 500);
  setTimeout(hideGenericSoundButton, 1500);
}

setupGlyphMusicControls();

  // ------- USER DISPLAY -------
  // Environment detection for API calls
  const isProduction = window.location.hostname === 'narrrfs.world' || window.location.hostname === 'narrrfs-world.onrender.com';
  const API_BASE_URL = isProduction ? 'https://narrrfs.world' : '';
  
  async function updateUserDisplay() {
    try {
      let discordId = safeLocalGet('discord_id') || safeSessionGet('discord_id') || '';
      let discordName = safeLocalGet('discord_name') || safeSessionGet('discord_name') || '';

      if (!userDisplayEl || !userNameEl) return;

      // Check for local bypass user (for local development)
      const isLocal = window.location.hostname === 'localhost' ||
                    window.location.hostname === '127.0.0.1' ||
                    window.location.hostname === '';

      if (!discordId && !isLocal) {
        try {
          const sessionRes = await fetch(`${API_BASE_URL}/api/user/get-session.php`, {
            credentials: 'include',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
          });
          if (sessionRes.ok) {
            const sessionData = await sessionRes.json();
            const sessionId = String(sessionData?.discord_id || '').trim();
            const sessionName = String(sessionData?.discord_username || '').trim();
            if (sessionId) {
              discordId = sessionId;
              discordName = sessionName || discordName || 'Player';
              safeLocalSet('discord_id', discordId);
              safeSessionSet('discord_id', discordId);
              if (discordName && discordName !== 'Player') {
                safeLocalSet('discord_name', discordName);
                safeSessionSet('discord_name', discordName);
              }
            }
          }
        } catch (sessionError) {
          console.warn('Glyph session check skipped:', sessionError);
        }
      }

      if (discordId && discordName) {
        // User is logged in with Discord - fetch full user details
        try {
          const response = await fetch(`${API_BASE_URL}/api/user/details.php?user_id=${encodeURIComponent(discordId)}`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
          });
          
          if (response.ok) {
            const data = await response.json();
            if (data.success && data.user) {
              // Update username
              if (userNameEl) {
                userNameEl.textContent = data.user.username || discordName;
              }
              
              // Update avatar (with fallback)
              if (userAvatarEl) {
                if (data.user.avatar_url) {
                  userAvatarEl.src = data.user.avatar_url;
                } else {
                  // Fallback to Discord default avatar based on user ID
                  const avatarIndex = parseInt(discordId) % 5;
                  userAvatarEl.src = `https://cdn.discordapp.com/embed/avatars/${avatarIndex}.png`;
                }
              }
              
              // Update dSPOINC balance (formatted with commas, like 3D riddle game)
              if (userBalanceEl) {
                const balance = data.user.balance || 0;
                userBalanceEl.textContent = `dSPOINC: ${typeof balance === 'number' && Number.isFinite(balance) ? balance.toLocaleString() : '0'}`;
              }
              
              // Show user display, hide login message
              userDisplayEl.style.display = 'flex';
              if (loginMessageEl) loginMessageEl.style.display = 'none';
              
              return;
            }
          }
        } catch (apiError) {
          console.warn('Failed to fetch user details from API, using localStorage data:', apiError);
        }
        
        // Fallback: Use localStorage data if API fails
        userNameEl.textContent = discordName;
        if (userAvatarEl) {
          const avatarIndex = parseInt(discordId) % 5;
          userAvatarEl.src = `https://cdn.discordapp.com/embed/avatars/${avatarIndex}.png`;
        }
        if (userBalanceEl) {
          userBalanceEl.textContent = '0';
        }
        userDisplayEl.style.display = 'flex';
        if (loginMessageEl) loginMessageEl.style.display = 'none';
        
      } else if (isLocal) {
        // Local development - show local bypass user
        if (userNameEl) {
          userNameEl.textContent = 'Local Bypass User';
        }
        if (userAvatarEl) {
          userAvatarEl.src = 'https://cdn.discordapp.com/embed/avatars/0.png';
        }
        if (userBalanceEl) {
          userBalanceEl.textContent = 'dSPOINC: 0';
        }
        userDisplayEl.style.display = 'flex';
        if (loginMessageEl) loginMessageEl.style.display = 'none';
        
      } else {
        // Not logged in - show login message, hide user display
        userDisplayEl.style.display = 'none';
        if (loginMessageEl) loginMessageEl.style.display = 'block';
      }
    } catch (error) {
      console.error('Error updating user display:', error);
      // On error, hide user display and show login message
      if (userDisplayEl) userDisplayEl.style.display = 'none';
      if (loginMessageEl) loginMessageEl.style.display = 'block';
    }
  }

  // ------- LEADERBOARD -------
  const leaderboardSection = document.getElementById('leaderboardSection');
  const leaderboardList = document.getElementById('leaderboardList');
  const leaderboardLoading = document.getElementById('leaderboardLoading');
  const leaderboardError = document.getElementById('leaderboardError');
  const tabButtons = document.querySelectorAll('.tab-btn');
  let currentLeaderboardDifficulty = 'easy';
  let leaderboardData = null;

  // Format date for display
  function formatDate(dateString) {
    if (!dateString) return '—';
    try {
      const date = new Date(dateString);
      const now = new Date();
      const diffMs = now - date;
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
      
      if (diffDays === 0) return 'Today';
      if (diffDays === 1) return 'Yesterday';
      if (diffDays < 7) return `${diffDays}d ago`;
      
      // Format as MM/DD or DD/MM depending on locale
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${month}/${day}`;
    } catch {
      return '—';
    }
  }

  function setActiveLeaderboardDifficulty(difficulty) {
    if (!DIFFICULTIES[difficulty]) return;
    currentLeaderboardDifficulty = difficulty;
    tabButtons.forEach((b) => {
      b.classList.toggle('active', b.getAttribute('data-difficulty') === difficulty);
    });
    if (leaderboardData) {
      displayLeaderboard(difficulty);
    }
  }

  // Fetch leaderboard from API
  async function fetchLeaderboard() {
    if (!leaderboardSection || !leaderboardList) return;
    
    try {
      // Show loading state
      leaderboardList.hidden = true;
      leaderboardError.hidden = true;
      leaderboardLoading.hidden = false;
      
      const response = await fetch('/api/dev/get-leaderboard.php', {
        credentials: 'include',
        cache: 'no-store'
      });
      const data = await response.json();
      
      if (data.success && data.glyph_memory) {
        if (data.current_season) {
          activeSeasonKey = String(data.current_season).trim();
          safeLocalSet('glyph_memory_active_season', activeSeasonKey);
        }
        leaderboardData = data.glyph_memory;

        const { discordId } = resolveGlyphScorePlayer();
        if (discordId) {
          Object.keys(DIFFICULTIES).forEach((diff) => {
            const entries = data.glyph_memory[diff] || [];
            const match = entries.find((entry) => String(entry.discord_id || '') === discordId);
            const serverBestMs = match ? Number(match.best_time_ms) : NaN;
            if (Number.isFinite(serverBestMs) && serverBestMs > 0) {
              const localBest = getBestTime(diff);
              if (localBest == null || serverBestMs < localBest) {
                setBestTime(diff, Math.round(serverBestMs));
              }
            }
          });
        }
        updateBestTimeUI(
          gameView && gameView.classList.contains('view--active')
            ? activeDifficulty
            : ((difficultySelect && difficultySelect.value) || activeDifficulty)
        );

        displayLeaderboard(currentLeaderboardDifficulty);
      } else {
        showLeaderboardError();
      }
    } catch (error) {
      console.error('Error fetching leaderboard:', error);
      showLeaderboardError();
    } finally {
      leaderboardLoading.hidden = true;
    }
  }

  // Display leaderboard entries
  function displayLeaderboard(difficulty) {
    if (!leaderboardList || !leaderboardData) return;
    
    leaderboardList.hidden = false;
    leaderboardError.hidden = true;
    leaderboardList.innerHTML = '';
    
    const entries = leaderboardData[difficulty] || [];
    
    if (entries.length === 0) {
      leaderboardList.innerHTML = '<div class="leaderboard-empty">No scores yet. Be the first!</div>';
      return;
    }
    
    entries.forEach((entry, index) => {
      const entryEl = document.createElement('div');
      entryEl.className = 'leaderboard-entry';
      entryEl.innerHTML = `
        <span class="leaderboard-rank">${index + 1}</span>
        <span class="leaderboard-name">${entry.discord_name || 'Guest'}</span>
        <span class="leaderboard-time">${entry.best_time_formatted || formatTime(entry.best_time_ms)}</span>
        <span class="leaderboard-date">${formatDate(entry.timestamp)}</span>
      `;
      leaderboardList.appendChild(entryEl);
    });
  }

  // Show error state
  function showLeaderboardError() {
    if (!leaderboardList || !leaderboardError) return;
    leaderboardList.hidden = true;
    leaderboardError.hidden = false;
  }

  // Handle tab switching
  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const difficulty = btn.getAttribute('data-difficulty');
      if (!difficulty) return;
      setActiveLeaderboardDifficulty(difficulty);
      if (!leaderboardData) {
        fetchLeaderboard();
      }
    });
  });

  window.addEventListener('storage', (ev) => {
    if (!ev || !ev.key || ['discord_id', 'discord_name'].includes(ev.key)) {
      updateUserDisplay();
    }
  });

  // ------- INIT -------
  // Ensure overlay is hidden on load
  winOverlay.hidden = true;
  setSaveStatus('', 'muted');
  if (newBestBadgeEl) newBestBadgeEl.hidden = true;
  updateBestTimeUI(difficultySelect.value || activeDifficulty);
  updateUserDisplay(); // Show user info on load
  fetchLeaderboard(); // Load leaderboard on page load
  showView('menu');
})();

