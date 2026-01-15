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
  // ------- CONFIG -------
  const DIFFICULTIES = {
    easy:   { pairs: 6,  cols: 4 }, // 3x4
    medium: { pairs: 8,  cols: 4 }, // 4x4
    hard:   { pairs: 12, cols: 6 }, // 6x4
  };

  // Your glyph filenames:
  const GLYPH_FILES = [
    ...Array.from({ length: 10 }, (_, i) => `assets/glyphs/${i}.png`),
    ...Array.from({ length: 26 }, (_, i) => `assets/glyphs/${String.fromCharCode(65 + i)}.png`),
  ];
  
  // CRITICAL: Verify GLYPH_FILES array is valid and has no duplicates
  (function validateGlyphFiles() {
    const unique = [...new Set(GLYPH_FILES)];
    if (unique.length !== GLYPH_FILES.length) {
      console.error(`❌ [GLYPH] GLYPH_FILES array contains duplicates! Total: ${GLYPH_FILES.length}, Unique: ${unique.length}`);
      console.error(`   This is a code bug - each glyph should appear exactly once in GLYPH_FILES`);
    }
    if (GLYPH_FILES.length < 12) {
      console.error(`❌ [GLYPH] Not enough glyph files! Need at least 12 for hard difficulty, have: ${GLYPH_FILES.length}`);
    }
    if (GLYPH_FILES.length === 36) {
      console.log(`✅ [GLYPH] Glyph files initialized: ${GLYPH_FILES.length} total (expected 36: 0-9 + A-Z)`);
    }
  })();

  // Backgrounds per difficulty (menu uses easy by default)
  const BACKGROUNDS = {
    menu: 'assets/backgrounds/bg_easy.jpg',
    easy: 'assets/backgrounds/bg_easy.jpg',
    medium: 'assets/backgrounds/bg_medium.jpg',
    hard: 'assets/backgrounds/bg_hard.jpg',
  };

  // Three sounds: match, mismatch, and card flip
  const SOUNDS = {
    match: 'assets/audio/match.mp3',
    fail:  'assets/audio/mismatch.mp3',
    flip:  'assets/audio/flip.mp3', // Sound when card is flipped up
  };

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
  const playAgainBtn = document.getElementById('playAgainBtn');
  const backToMenuBtn = document.getElementById('backToMenuBtn');

  // ------- STATE -------
  let activeDifficulty = 'easy';
  let deck = []; // { id, glyphSrc, matched }

  let firstPick = null;
  let secondPick = null;
  let lockBoard = false;

  let matchedPairs = 0;

  let timerStart = 0;
  let timerInterval = null;

  // Best times (stored locally per difficulty)
  const BEST_TIME_PREFIX = 'glyph_memory_best_ms_';

  function getBestKey(difficultyKey) {
    return `${BEST_TIME_PREFIX}${difficultyKey}`;
  }

  function getBestTimeMs(difficultyKey) {
    try {
      const raw = window.localStorage.getItem(getBestKey(difficultyKey));
      if (!raw) return null;
      const n = Number(raw);
      return Number.isFinite(n) && n > 0 ? n : null;
    } catch {
      return null;
    }
  }

  function setBestTimeMs(difficultyKey, ms) {
    try {
      window.localStorage.setItem(getBestKey(difficultyKey), String(ms));
    } catch {
      // ignore (private mode / storage blocked)
    }
  }

  function updateBestTimeUI(difficultyKey) {
    const bestMs = getBestTimeMs(difficultyKey);
    const label = bestMs ? formatTime(bestMs) : '—';
    if (bestTimeEl) bestTimeEl.textContent = label;
    if (menuBestTimeEl) menuBestTimeEl.textContent = label;
    if (overlayBestTimeEl) overlayBestTimeEl.textContent = label;
  }

  // Audio (graceful)
  const audio = {
    match: SOUNDS.match ? new Audio(SOUNDS.match) : null,
    fail:  SOUNDS.fail ? new Audio(SOUNDS.fail) : null,
    flip:  SOUNDS.flip ? new Audio(SOUNDS.flip) : null,
  };

  function playSound(key) {
    const a = audio[key];
    if (!a) return;
    try {
      a.currentTime = 0;
      void a.play();
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

// ------- HELPERS -------
  function setBackground(kind) {
    // kind: 'menu' | 'easy' | 'medium' | 'hard'
    const url = BACKGROUNDS[kind] || '';
    if (!url) {
      bg.style.backgroundImage = '';
      return;
    }
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
    } else {
      setBackground(activeDifficulty);
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
    const totalSec = Math.floor(ms / 1000);
    const min = Math.floor(totalSec / 60);
    const sec = totalSec % 60;
    return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
  }

  // ------- BEST TIME (per difficulty) -------
  function bestKey(diff) {
    return `glyphMemoryBestTime:${diff}`;
  }

  function getBestTime(diff) {
    try {
      const raw = window.localStorage.getItem(bestKey(diff));
      const n = raw ? Number(raw) : NaN;
      return Number.isFinite(n) && n > 0 ? n : null;
    } catch {
      return null;
    }
  }

  function setBestTime(diff, ms) {
    try {
      window.localStorage.setItem(bestKey(diff), String(ms));
    } catch {
      // ignore (private mode etc.)
    }
  }

  function updateBestTimeUI(diff) {
    const best = getBestTime(diff);
    const label = best == null ? '—' : formatTime(best);
    if (bestTimeEl) bestTimeEl.textContent = label;
    if (menuBestTimeEl) menuBestTimeEl.textContent = label;
    if (overlayBestTimeEl) overlayBestTimeEl.textContent = label;
  }

  function startTimer() {
    stopTimer();
    timerStart = Date.now();
    timerEl.textContent = '00:00';
    timerInterval = window.setInterval(() => {
      const elapsed = Date.now() - timerStart;
      timerEl.textContent = formatTime(elapsed);
    }, 250);
  }

  function stopTimer() {
    if (timerInterval) {
      window.clearInterval(timerInterval);
      timerInterval = null;
    }
  }

  function resetTurnPicks() {
    firstPick = null;
    secondPick = null;
    lockBoard = false;
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
    const cfg = DIFFICULTIES[difficultyKey];
    const totalCards = cfg.pairs * 2;
    const cols = cfg.cols;

    boardEl.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;

    // Card size: scale up to use the available empty space, while staying responsive
    const usableWidth = Math.min(1400, Math.floor(window.innerWidth * 0.96));
    const gutter = 22; // matches CSS gap
    const perCol = Math.floor((usableWidth - gutter * (cols - 1)) / cols);
    // Clamp so easy/medium can be larger, hard stays sensible
    const maxCard = Math.max(140, Math.min(240, perCol));
    boardEl.style.gridAutoRows = `${maxCard}px`;

    const rows = Math.ceil(totalCards / cols);
    boardEl.setAttribute('aria-label', `Card grid ${rows} by ${cols}`);
  }

  function renderBoard() {
    boardEl.innerHTML = '';
    matchedPairs = 0;

    const frag = document.createDocumentFragment();

    deck.forEach((card, index) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'card';
      btn.dataset.index = String(index);
      btn.setAttribute('role', 'gridcell');
      btn.setAttribute('aria-label', 'Hidden card');

      const back = document.createElement('div');
      back.className = 'cardInner cardBack';
      back.innerHTML = '<div class="backIcon" aria-hidden="true"></div><div class="backDots" aria-hidden="true"></div>';

      const front = document.createElement('div');
      front.className = 'cardInner cardFront';

      const img = document.createElement('img');
      img.alt = 'Glyph';
      // Use eager loading so glyphs are visible immediately on flip
      img.loading = 'eager';
      img.decoding = 'async';
      img.src = card.glyphSrc;
      // If a glyph file is missing or fails to load, show a clear fallback + log it
      img.addEventListener('error', () => {
        console.warn('Missing glyph file:', card.glyphSrc);
        front.textContent = 'MISSING';
        front.classList.add('missing');
      }, { once: true });
      img.draggable = false;
      // Optional: Auto-center glyph pixels so they look consistent at all card sizes.
      // Disabled by default because it uses data: URLs which may be blocked by CSP.
      if (ENABLE_GLYPH_NORMALIZATION) {
        normalizeGlyphImage(card.glyphSrc).then((dataUrl) => {
          if (!front.classList.contains('missing')) img.src = dataUrl;
        });
      }

      front.appendChild(img);
      btn.appendChild(back);
      btn.appendChild(front);

      btn.addEventListener('click', onCardClick);

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
  function onCardClick(e) {
    if (lockBoard) return;

    const cardBtn = e.currentTarget;
    const index = Number(cardBtn.dataset.index);
    const card = deck[index];

    if (!card) {
      console.error(`❌ [GLYPH] Card at index ${index} not found in deck!`);
      return;
    }

    if (card.matched) return;
    if (cardBtn.classList.contains('is-flipped')) return;

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

    lockBoard = true;

    // CRITICAL: Use strict comparison and verify glyphSrc values
    const firstSrc = String(firstCard.glyphSrc || '').trim();
    const secondSrc = String(secondCard.glyphSrc || '').trim();
    
    if (!firstSrc || !secondSrc) {
      console.error(`❌ [GLYPH] Empty glyphSrc detected! First: "${firstSrc}", Second: "${secondSrc}"`);
      playSound('fail');
      window.setTimeout(() => {
        flipCardDown(firstPick.el);
        flipCardDown(secondPick.el);
        resetTurnPicks();
      }, 900);
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
      playSound('match');

      window.setTimeout(() => {
        resetTurnPicks();
        checkWin();
      }, 220);

    } else {
      playSound('fail');

      window.setTimeout(() => {
        flipCardDown(firstPick.el);
        flipCardDown(secondPick.el);
        resetTurnPicks();
      }, 900);
    }
  }

  function flipCardUp(el) {
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

  function checkWin() {
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
        return; // Don't allow win with corrupted deck
      }
    }
    
    if (matchedPairs >= totalPairs) {
      // CRITICAL: Final validation - verify all cards are actually matched
      const stillUnmatched = deck.filter(card => !card.matched);
      if (stillUnmatched.length > 0) {
        console.error(`❌ [GLYPH] Win condition met but ${stillUnmatched.length} card(s) still unmatched!`, stillUnmatched);
        // Don't show win screen if there are still unmatched cards
        return;
      }
      
      stopTimer();

      const elapsed = Date.now() - timerStart;
      finalTimeEl.textContent = formatTime(elapsed);

      // Best time logic (per difficulty)
      const prevBest = getBestTime(activeDifficulty);
      const isNewBest = prevBest == null || elapsed < prevBest;
      if (isNewBest) setBestTime(activeDifficulty, elapsed);
      updateBestTimeUI(activeDifficulty);

      // 🧩 Save score to database if Discord is logged in
      saveGlyphScore(activeDifficulty, elapsed, totalPairs);

      if (newBestBadgeEl) newBestBadgeEl.hidden = !isNewBest;

      console.log(`✅ [GLYPH] Game won! Time: ${formatTime(elapsed)}, Pairs matched: ${matchedPairs}/${totalPairs}`);
      winOverlay.hidden = false;
    }
  }

  // 🧩 Save Glyph Memory score to database (if Discord logged in)
  async function saveGlyphScore(difficulty, timeMs, pairsMatched) {
    // Check if Discord is logged in
    const discordId = localStorage.getItem("discord_id");
    const discordName = localStorage.getItem("discord_name");
    
    if (!discordId) {
      // Not logged in - use localStorage only (existing behavior)
      console.log("🧩 Not logged in - score saved to localStorage only");
      return;
    }

    try {
      const response = await fetch('/api/dev/save-score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          game: 'glyph_memory',
          discord_id: discordId,
          discord_name: discordName || "Player",
          difficulty: difficulty,
          time_ms: timeMs,
          pairs_matched: pairsMatched,
          wallet: '' // Not required for glyph memory
        })
      });

      const data = await response.json();
      if (data.success) {
        console.log("✅ Glyph Memory score saved to database:", data.message);
        // Refresh leaderboard after saving score
        fetchLeaderboard();
      } else {
        console.warn("⚠️ Failed to save Glyph Memory score:", data.error);
      }
    } catch (error) {
      console.error("❌ Error saving Glyph Memory score:", error);
      // Don't show error to user - localStorage fallback already saved the best time
    }
  }

  // ------- FLOW CONTROLS -------
  function startGame() {
    activeDifficulty = difficultySelect.value;

    winOverlay.hidden = true;
    resetTurnPicks();

    deck = buildDeck(activeDifficulty);
    
    // CRITICAL: Verify deck integrity before starting game
    if (!verifyDeckIntegrity()) {
      console.error(`❌ [GLYPH] Cannot start game - deck is corrupted!`);
      alert('⚠️ Error: Game deck is invalid. Please try restarting the game.');
      return; // Don't start with corrupted deck
    }
    
    // CRITICAL: Verify deck has correct number of cards
    const expectedCards = DIFFICULTIES[activeDifficulty].pairs * 2;
    if (deck.length !== expectedCards) {
      console.error(`❌ [GLYPH] Deck has wrong number of cards: expected ${expectedCards}, got ${deck.length}`);
      alert('⚠️ Error: Game deck has incorrect number of cards. Please try restarting the game.');
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
      return;
    }
    
    console.log(`✅ [GLYPH] Deck validated successfully: ${deck.length} cards, ${Object.keys(glyphCounts).length} unique glyphs, all appear exactly 2 times`);
    
    setBoardGrid(activeDifficulty);
    renderBoard();

    updateBestTimeUI(activeDifficulty);
    if (newBestBadgeEl) newBestBadgeEl.hidden = true;

    showView('game');
    startTimer();
  }

  function restartGame() {
    winOverlay.hidden = true;
    resetTurnPicks();

    deck = buildDeck(activeDifficulty);
    
    // CRITICAL: Verify deck integrity before restarting game
    if (!verifyDeckIntegrity()) {
      console.error(`❌ [GLYPH] Cannot restart game - deck is corrupted!`);
      alert('⚠️ Error: Game deck is invalid. Please try restarting the game.');
      goToMenu(); // Return to menu if deck is corrupted
      return;
    }
    
    // CRITICAL: Verify deck has correct number of cards
    const expectedCards = DIFFICULTIES[activeDifficulty].pairs * 2;
    if (deck.length !== expectedCards) {
      console.error(`❌ [GLYPH] Deck has wrong number of cards: expected ${expectedCards}, got ${deck.length}`);
      goToMenu();
      return;
    }
    
    setBoardGrid(activeDifficulty);
    renderBoard();

    updateBestTimeUI(activeDifficulty);
    if (newBestBadgeEl) newBestBadgeEl.hidden = true;

    startTimer();
  }

  function goToMenu() {
    stopTimer();
    winOverlay.hidden = true;
    resetTurnPicks();
    if (newBestBadgeEl) newBestBadgeEl.hidden = true;
    showView('menu');
  }

  // Recompute card sizing on resize (keeps bigger cards responsive)
  window.addEventListener('resize', () => {
    const gameIsActive = document.getElementById('gameView').classList.contains('view--active');
    if (gameIsActive && activeDifficulty) setBoardGrid(activeDifficulty);
  });

// ------- EVENTS -------
  startBtn.addEventListener('click', startGame);
  difficultySelect.addEventListener('change', () => {
    // Update the menu best-time preview for the currently selected difficulty
    updateBestTimeUI(difficultySelect.value);
  });
  restartBtn.addEventListener('click', restartGame);
  menuBtn.addEventListener('click', goToMenu);

  playAgainBtn.addEventListener('click', restartGame);
  backToMenuBtn.addEventListener('click', goToMenu);

  // Prevent any drag previews in-game
  document.addEventListener('dragstart', (ev) => {
    if (ev.target && (ev.target.tagName === 'IMG' || ev.target.closest?.('.card'))) {
      ev.preventDefault();
    }
  });

  // ------- USER DISPLAY -------
  // Environment detection for API calls
  const isProduction = window.location.hostname === 'narrrfs.world' || window.location.hostname === 'narrrfs-world.onrender.com';
  const API_BASE_URL = isProduction ? 'https://narrrfs.world' : '';
  
  async function updateUserDisplay() {
    try {
      const discordId = localStorage.getItem('discord_id');
      const discordName = localStorage.getItem('discord_name');
      
      if (!userDisplayEl || !userNameEl) return;
      
      // Check for local bypass user (for local development)
      const isLocal = window.location.hostname === 'localhost' || 
                    window.location.hostname === '127.0.0.1' || 
                    window.location.hostname === '';
      
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
              
              // Update DSPOINC balance (formatted with commas, like 3D riddle game)
              if (userBalanceEl) {
                const balance = data.user.balance || 0;
                userBalanceEl.textContent = `DSPOINC: ${typeof balance === 'number' && Number.isFinite(balance) ? balance.toLocaleString() : '0'}`;
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
          userBalanceEl.textContent = 'DSPOINC: 0';
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

  // Fetch leaderboard from API
  async function fetchLeaderboard() {
    if (!leaderboardSection || !leaderboardList) return;
    
    try {
      // Show loading state
      leaderboardList.hidden = true;
      leaderboardError.hidden = true;
      leaderboardLoading.hidden = false;
      
      const response = await fetch('/api/dev/get-leaderboard.php');
      const data = await response.json();
      
      if (data.success && data.glyph_memory) {
        leaderboardData = data.glyph_memory;
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
      
      // Update active tab
      tabButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      // Update displayed leaderboard
      currentLeaderboardDifficulty = difficulty;
      if (leaderboardData) {
        displayLeaderboard(difficulty);
      } else {
        fetchLeaderboard();
      }
    });
  });

  // ------- INIT -------
  // Ensure overlay is hidden on load
  winOverlay.hidden = true;
  if (newBestBadgeEl) newBestBadgeEl.hidden = true;
  updateBestTimeUI(difficultySelect.value || activeDifficulty);
  updateUserDisplay(); // Show user info on load
  fetchLeaderboard(); // Load leaderboard on page load
  showView('menu');
})();

