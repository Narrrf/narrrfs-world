(function () {
    const PAGE_MAP = {
        'index.html': 'index',
        '': 'index',
        'projects.html': 'projects',
        'about.html': 'about',
        'contact.html': 'contact',
        'legal.html': 'legal'
    };

    const COLOR_MAP = {
        gold: '#facc15',
        blue: '#38bdf8',
        silver: '#e5e7eb',
        red: '#fb7185',
        cyan: '#22d3ee',
        white: '#ffffff'
    };

    let samuziThunderUnlocked = false;

    function getCurrentPageKey() {
        const file = window.location.pathname.split('/').pop() || 'index.html';
        return PAGE_MAP[file] || file.replace('.html', '');
    }

    function normalizeEffect(effect) {
        const allowed = [
            'off',
            'golden-lightning',
            'blue-lightning',
            'silver-lightning',
            'shadow-storm',
            'cyber-storm'
        ];

        return allowed.includes(effect) ? effect : 'off';
    }

    function getEffectColor(settings) {
        const selected = settings.bubble_color || 'gold';

        if (selected === 'custom') {
            return settings.custom_bubble_color || '#facc15';
        }

        if (selected.startsWith && selected.startsWith('#')) {
            return selected;
        }

        if (settings.bubble_effect === 'golden-lightning') return COLOR_MAP.gold;
        if (settings.bubble_effect === 'blue-lightning') return COLOR_MAP.blue;
        if (settings.bubble_effect === 'silver-lightning') return COLOR_MAP.silver;
        if (settings.bubble_effect === 'shadow-storm') return COLOR_MAP.red;
        if (settings.bubble_effect === 'cyber-storm') return COLOR_MAP.cyan;

        return COLOR_MAP[selected] || COLOR_MAP.gold;
    }

    function getIntensityValue(intensity) {
        switch (intensity) {
            case 'low': return '0.42';
            case 'high': return '0.78';
            case 'cinematic': return '0.95';
            case 'medium':
            default: return '0.62';
        }
    }

    function getSpeedDelay(speed, effect = '') {
    if (effect === 'cyber-storm') {
        switch (speed) {
            case 'slow': return [3200, 6400];
            case 'fast': return [900, 2200];
            case 'storm': return [420, 1500];
            case 'medium':
            default: return [1800, 3800];
        }
    }

    if (effect === 'shadow-storm') {
        switch (speed) {
            case 'slow': return [4600, 8200];
            case 'fast': return [1500, 3400];
            case 'storm': return [700, 2300];
            case 'medium':
            default: return [2600, 5400];
        }
    }

    switch (speed) {
        case 'slow': return [5200, 9200];
        case 'fast': return [1800, 4200];
        case 'storm': return [900, 2800];
        case 'medium':
        default: return [3000, 6200];
    }
}

    function randomBetween(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function createLayer(settings) {
        const existing = document.querySelector('.samuzi-effect-layer');
        if (existing) {
            existing.remove();
        }

        const effect = normalizeEffect(settings.bubble_effect);
        if (effect === 'off') {
            document.body.classList.remove('samuzi-effects-active');
            return null;
        }

        const layer = document.createElement('div');
        layer.className = 'samuzi-effect-layer';
        layer.dataset.effect = effect;
        layer.style.setProperty('--samuzi-lightning-color', getEffectColor(settings));
        layer.style.setProperty('--samuzi-lightning-opacity', getIntensityValue(settings.bubble_opacity));

        const flash = document.createElement('div');
        flash.className = 'samuzi-lightning-flash';

        const shadow = document.createElement('div');
        shadow.className = 'samuzi-lightning-shadow';

        layer.appendChild(flash);
        layer.appendChild(shadow);
        document.body.appendChild(layer);
        document.body.classList.add('samuzi-effects-active');

        return layer;
    }

    function createBolt(layer) {
    if (!layer) return;

    const effect = layer.dataset.effect || 'golden-lightning';
    const motionActive = document.body.classList.contains('phase3-motion-preview-active');

    const boltCount = motionActive
    ? 1
    : effect === 'cyber-storm'
        ? randomBetween(2, 3)
        : effect === 'shadow-storm'
            ? randomBetween(1, 2)
            : 1;

    for (let i = 0; i < boltCount; i++) {
        const bolt = document.createElement('div');
        bolt.className = 'samuzi-lightning-bolt';

        if (effect === 'golden-lightning') {
            bolt.classList.add('bolt-gold');
        }

        if (effect === 'blue-lightning') {
            bolt.classList.add('bolt-blue');
        }

        if (effect === 'silver-lightning') {
            bolt.classList.add('bolt-silver');
        }

        if (effect === 'shadow-storm') {
            bolt.classList.add('bolt-shadow');
        }

        if (effect === 'cyber-storm') {
            bolt.classList.add('bolt-cyber');
        }

        const side = Math.random() > 0.5 ? 'left' : 'right';

        let top = randomBetween(4, 72);
        let height = randomBetween(120, 360);
        let width = 6;
        let rotation = side === 'left'
            ? randomBetween(-24, 20)
            : randomBetween(-20, 24);

        if (effect === 'golden-lightning') {
            top = randomBetween(8, 58);
            height = randomBetween(170, 420);
            width = 7;
        }

        if (effect === 'blue-lightning') {
            top = randomBetween(3, 76);
            height = randomBetween(160, 390);
            width = 5;
            rotation += side === 'left' ? -10 : 10;
        }

        if (effect === 'silver-lightning') {
            top = randomBetween(6, 68);
            height = randomBetween(110, 260);
            width = 4;
        }

        if (effect === 'shadow-storm') {
            top = randomBetween(12, 82);
            height = randomBetween(220, 520);
            width = 8;
        }

        if (effect === 'cyber-storm') {
            top = randomBetween(2, 78);
            height = randomBetween(130, 330);
            width = 4;
            rotation += randomBetween(-18, 18);
        }

        bolt.style.top = `${top}vh`;
        bolt.style.height = `${height}px`;
        bolt.style.width = `${width}px`;
        bolt.style[side] = `${randomBetween(2, 26)}vw`;
        bolt.style.transform = `rotate(${rotation}deg)`;

        layer.appendChild(bolt);

        setTimeout(() => {
            bolt.remove();
        }, effect === 'silver-lightning' ? 520 : 820);
    }

    layer.classList.add('is-flashing');

    if (effect === 'shadow-storm') {
        layer.classList.add('is-shadow-storming');
    }

    if (effect === 'cyber-storm') {
        layer.classList.add('is-cyber-striking');
    }

    if (samuziThunderUnlocked && Math.random() > 0.52) {
        playSoftThunder();
    }

    setTimeout(() => {
        layer.classList.remove('is-flashing');
        layer.classList.remove('is-shadow-storming');
        layer.classList.remove('is-cyber-striking');
    }, 920);
}

    function playSoftThunder() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;

        const ctx = new AudioContext();
        const now = ctx.currentTime;

        const master = ctx.createGain();
        master.gain.setValueAtTime(0.0001, now);
        master.gain.exponentialRampToValueAtTime(0.16, now + 0.08);
        master.gain.exponentialRampToValueAtTime(0.0001, now + 1.2);
        master.connect(ctx.destination);

        const filter = ctx.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(120, now);
        filter.frequency.exponentialRampToValueAtTime(70, now + 1.1);
        filter.connect(master);

        const buffer = ctx.createBuffer(1, ctx.sampleRate * 1.25, ctx.sampleRate);
        const data = buffer.getChannelData(0);

        for (let i = 0; i < data.length; i++) {
            const progress = i / data.length;
            const fade = Math.pow(1 - progress, 2);
            data[i] = (Math.random() * 2 - 1) * fade;
        }

        const source = ctx.createBufferSource();
        source.buffer = buffer;
        source.connect(filter);
        source.start(now);
        source.stop(now + 1.25);

        setTimeout(() => {
            ctx.close().catch(() => {});
        }, 1600);
    }

   function scheduleLightning(layer, settings) {
    const [minDelay, maxDelay] = getSpeedDelay(settings.bubble_speed, settings.bubble_effect);

    function tick() {
        if (!document.body.contains(layer)) return;

        const motionActive = document.body.classList.contains('phase3-motion-preview-active');

        if (motionActive) {
            if (Math.random() > 0.45) {
                createBolt(layer);
            }

            setTimeout(tick, randomBetween(4200, 7600));
            return;
        }

        createBolt(layer);

        const delay = randomBetween(minDelay, maxDelay);
        setTimeout(tick, delay);
    }

    setTimeout(tick, randomBetween(900, 1800));
}

    async function initSamuziVisualEffects() {
        try {
            const response = await fetch('./api/get-bubble-settings.php', { cache: 'no-store' });
            const result = await response.json();

            if (!result || !result.success) {
                return;
            }

            const pageKey = getCurrentPageKey();
            const pageEnabled = result.bubble_pages && result.bubble_pages[pageKey];

            if (!pageEnabled) {
                return;
            }

            const settings = {
                bubble_effect: normalizeEffect(result.bubble_effect),
                bubble_color: result.bubble_color || 'gold',
                bubble_opacity: result.bubble_opacity || 'medium',
                bubble_speed: result.bubble_speed || 'medium',
                custom_bubble_color: result.custom_bubble_color || ''
            };

            const layer = createLayer(settings);

            if (layer) {
                scheduleLightning(layer, settings);
            }
        } catch (error) {
            console.warn('[SAMUZI] Visual effects could not be loaded.', error);
        }
    }

    document.addEventListener('pointerdown', () => {
        samuziThunderUnlocked = true;
    }, { once: true });

    document.addEventListener('DOMContentLoaded', initSamuziVisualEffects);
})();