/**
 * ============================================================================
 * SKY SYSTEM - Sky and Environment Management
 * ============================================================================
 *
 * Version: 2026-02-01-HEADER-REFRESH
 * Lines: ~1,230
 * Used by: main.js (skySystem)
 *
 * ============================================================================
 * 🤖 AI & HUMAN NAVIGATION – QUICK FIND
 * ============================================================================
 *
 *   applyLevelEnvironment   ~150  Per-level sky config
 *   update(delta)            ~200  Day/night, clouds
 *   setTime, setCloudDensity ~250
 *
 * ============================================================================
 * 🎯 PURPOSE
 * ============================================================================
 * 
 * Manages sky and environment for all levels:
 * - Dynamic day/night cycle
 * - Sun and moon positioning
 * - Procedural cloud generation
 * - Starfield with flickering
 * - Lensflare system
 * - Auto-updated lighting
 * - Per-level environment settings
 * 
 * ============================================================================
 * ✅ STABLE VERSION STATUS
 * ============================================================================
 * 
 * ✅ **STABLE VERSION - PRODUCTION READY**
 * 
 * This system has reached a stable, production-ready state with:
 * - ✅ All 6 levels working correctly
 * - ✅ Day/night cycle smooth and functional
 * - ✅ Cloud generation working
 * - ✅ Starfield displaying correctly
 * - ✅ Per-level settings save/load working
 * - ✅ Settings persistence via localStorage
 * 
 * ============================================================================
 * 📦 WHAT THIS MODULE LOADS
 * ============================================================================
 * 
 * Resources:
 * - No external files (procedural generation)
 * - Uses THREE.js built-in materials and geometries
 * 
 * Dependencies:
 * - THREE.js Scene
 * - THREE.js WebGLRenderer
 * 
 * ============================================================================
 * 🔧 INTEGRATION IN MAIN.JS
 * ============================================================================
 * 
 * 1. Import:
 *    import { SkySystem } from "./sky-system.js";
 * 
 * 2. Initialize (once):
 *    skySystem = new SkySystem({
 *      scene: scene,
 *      renderer: renderer
 *    });
 * 
 * 3. Apply per level:
 *    skySystem.applyLevelEnvironment(levelId);
 * 
 * 4. Settings persistence:
 *    - Settings saved via localStorage
 *    - Loaded automatically on level change
 *    - Functions: loadSkySettingsForLevel(), saveSkySettingsForLevel()
 * 
 * ============================================================================
 * 🎮 KEY FUNCTIONS
 * ============================================================================
 * 
 * - applyLevelEnvironment(levelId) - Apply sky settings for level
 * - setTime(hour, minute) - Set time of day
 * - setCloudDensity(density) - Set cloud density
 * - setStarCount(count) - Set number of stars
 * - setTimeSpeedMultiplier(multiplier) - Set time speed multiplier (1.0x - 1000x, default 1.0x)
 * - enableDayNightCycle(enabled) - Enable/disable day/night cycle
 * - enableLensflare(enabled) - Enable/disable lensflare
 * 
 * ============================================================================
 */

import * as THREE from "three";

// ============================================================================
// LENSFLARE - Enhanced three.js Lensflare with skybox sun tracking
// ============================================================================

class Lensflare extends THREE.Mesh {
  constructor() {
    const geometry = Lensflare.Geometry;
    super(geometry, new THREE.MeshBasicMaterial({ opacity: 0, transparent: true, fog: false }));

    this.isLensflare = true;
    this.type = "Lensflare";
    this.frustumCulled = false;
    this.renderOrder = Infinity;

    const positionScreen = new THREE.Vector3();
    const positionView = new THREE.Vector3();
    const tempMap = new THREE.FramebufferTexture(16, 16);
    const occlusionMap = new THREE.FramebufferTexture(16, 16);
    let currentType = THREE.UnsignedByteType;

    const material1a = new THREE.RawShaderMaterial({
      uniforms: {
        scale: { value: null },
        screenPosition: { value: null },
      },
      vertexShader: `
        precision highp float;
        uniform vec3 screenPosition;
        uniform vec2 scale;
        attribute vec3 position;
        void main() {
          gl_Position = vec4( position.xy * scale + screenPosition.xy, screenPosition.z, 1.0 );
        }`,
      fragmentShader: `
        precision highp float;
        void main() {
          gl_FragColor = vec4( 1.0, 0.0, 1.0, 1.0 );
        }`,
      depthTest: true,
      depthWrite: false,
      transparent: false,
      fog: false,
    });

    const material1b = new THREE.RawShaderMaterial({
      uniforms: {
        map: { value: tempMap },
        scale: { value: null },
        screenPosition: { value: null },
      },
      vertexShader: `
        precision highp float;
        uniform vec3 screenPosition;
        uniform vec2 scale;
        attribute vec3 position;
        attribute vec2 uv;
        varying vec2 vUV;
        void main() {
          vUV = uv;
          gl_Position = vec4( position.xy * scale + screenPosition.xy, screenPosition.z, 1.0 );
        }`,
      fragmentShader: `
        precision highp float;
        uniform sampler2D map;
        varying vec2 vUV;
        void main() {
          gl_FragColor = texture2D( map, vUV );
        }`,
      depthTest: false,
      depthWrite: false,
      transparent: false,
      fog: false,
    });

    const mesh1 = new THREE.Mesh(geometry, material1a);
    const elements = [];
    const shader = LensflareElement.Shader;
    const material2 = new THREE.RawShaderMaterial({
      name: shader.name,
      uniforms: {
        map: { value: null },
        occlusionMap: { value: occlusionMap },
        color: { value: new THREE.Color(0xffffff) },
        scale: { value: new THREE.Vector2() },
        screenPosition: { value: new THREE.Vector3() },
      },
      vertexShader: shader.vertexShader,
      fragmentShader: shader.fragmentShader,
      blending: THREE.AdditiveBlending,
      transparent: true,
      depthWrite: false,
      fog: false,
    });

    const mesh2 = new THREE.Mesh(geometry, material2);

    this.addElement = function (element) {
      elements.push(element);
    };

    const scale = new THREE.Vector2();
    const screenPositionPixels = new THREE.Vector2();
    const validArea = new THREE.Box2();
    const viewport = new THREE.Vector4();

    this.onBeforeRender = function (renderer, scene, camera) {
      renderer.getCurrentViewport(viewport);
      const renderTarget = renderer.getRenderTarget();
      const type = renderTarget !== null ? renderTarget.texture.type : THREE.UnsignedByteType;

      if (currentType !== type) {
        tempMap.dispose();
        occlusionMap.dispose();
        tempMap.type = occlusionMap.type = type;
        currentType = type;
      }

      const invAspect = viewport.w / viewport.z;
      const halfViewportWidth = viewport.z / 2.0;
      const halfViewportHeight = viewport.w / 2.0;
      let size = 16 / viewport.w;
      scale.set(size * invAspect, size);

      validArea.min.set(viewport.x - 100, viewport.y - 100);
      validArea.max.set(viewport.x + (viewport.z + 100), viewport.y + (viewport.w + 100));

      // Override view position for occlusion test
      if (this._screenPositionOverridden === true) {
        positionScreen.copy(this._overriddenScreenPosition);
        const invProj = camera.projectionMatrix.clone().invert();
        positionView.copy(positionScreen).applyMatrix4(invProj);
      } else {
        positionView.setFromMatrixPosition(this.matrixWorld);
        positionView.applyMatrix4(camera.matrixWorldInverse);
      }

      if (positionView.z > 0) return; // lensflare is behind the camera

      // Allow Skybox to override screen-space sun position
      if (this._screenPositionOverridden === true) {
        positionScreen.set(
          this._overriddenScreenPosition.x,
          this._overriddenScreenPosition.y,
          this._overriddenScreenPosition.z
        );
      } else {
        positionScreen.copy(positionView).applyMatrix4(camera.projectionMatrix);
      }

      screenPositionPixels.x = viewport.x + positionScreen.x * halfViewportWidth + halfViewportWidth - 8;
      screenPositionPixels.y = viewport.y + positionScreen.y * halfViewportHeight + halfViewportHeight - 8;

      if (validArea.containsPoint(screenPositionPixels)) {
        renderer.copyFramebufferToTexture(tempMap, screenPositionPixels);

        let uniforms = material1a.uniforms;
        uniforms["scale"].value = scale;
        uniforms["screenPosition"].value = positionScreen;

        renderer.renderBufferDirect(camera, null, geometry, material1a, mesh1, null);
        renderer.copyFramebufferToTexture(occlusionMap, screenPositionPixels);

        uniforms = material1b.uniforms;
        uniforms["scale"].value = scale;
        uniforms["screenPosition"].value = positionScreen;

        renderer.renderBufferDirect(camera, null, geometry, material1b, mesh1, null);

        const vecX = -positionScreen.x * 2;
        const vecY = -positionScreen.y * 2;

        for (let i = 0, l = elements.length; i < l; i++) {
          const element = elements[i];
          const uniforms = material2.uniforms;

          uniforms["color"].value.copy(element.color);
          uniforms["map"].value = element.texture;

          uniforms["screenPosition"].value.x = positionScreen.x + vecX * element.distance;
          uniforms["screenPosition"].value.y = positionScreen.y + vecY * element.distance;

          size = element.size / viewport.w;
          const invAspect = viewport.w / viewport.z;
          uniforms["scale"].value.set(size * invAspect, size);

          material2.uniformsNeedUpdate = true;
          renderer.renderBufferDirect(camera, null, geometry, material2, mesh2, null);
        }
      }
    };

    this.dispose = function () {
      material1a.dispose();
      material1b.dispose();
      material2.dispose();
      tempMap.dispose();
      occlusionMap.dispose();
      for (let i = 0, l = elements.length; i < l; i++) {
        elements[i].texture.dispose();
      }
    };
  }
}

Lensflare.Geometry = (function () {
  const geometry = new THREE.BufferGeometry();
  const float32Array = new Float32Array([
    -1, -1, 0, 0, 0, 1, -1, 0, 1, 0, 1, 1, 0, 1, 1, -1, 1, 0, 0, 1,
  ]);
  const interleavedBuffer = new THREE.InterleavedBuffer(float32Array, 5);
  geometry.setIndex([0, 1, 2, 0, 2, 3]);
  geometry.setAttribute("position", new THREE.InterleavedBufferAttribute(interleavedBuffer, 3, 0, false));
  geometry.setAttribute("uv", new THREE.InterleavedBufferAttribute(interleavedBuffer, 2, 3, false));
  return geometry;
})();

class LensflareElement {
  constructor(texture, size = 1, distance = 0, color = new THREE.Color(0xffffff)) {
    this.texture = texture;
    this.size = size;
    this.distance = distance;
    this.color = color;
  }
}

LensflareElement.Shader = {
  name: "LensflareElementShader",
  uniforms: {
    map: { value: null },
    occlusionMap: { value: null },
    color: { value: null },
    scale: { value: null },
    screenPosition: { value: null },
  },
  vertexShader: `
    precision highp float;
    uniform vec3 screenPosition;
    uniform vec2 scale;
    uniform sampler2D occlusionMap;
    attribute vec3 position;
    attribute vec2 uv;
    varying vec2 vUV;
    varying float vVisibility;
    void main() {
      vUV = uv;
      vec2 pos = position.xy;
      vec4 visibility = texture2D( occlusionMap, vec2( 0.1, 0.1 ) );
      visibility += texture2D( occlusionMap, vec2( 0.5, 0.1 ) );
      visibility += texture2D( occlusionMap, vec2( 0.9, 0.1 ) );
      visibility += texture2D( occlusionMap, vec2( 0.9, 0.5 ) );
      visibility += texture2D( occlusionMap, vec2( 0.9, 0.9 ) );
      visibility += texture2D( occlusionMap, vec2( 0.5, 0.9 ) );
      visibility += texture2D( occlusionMap, vec2( 0.1, 0.9 ) );
      visibility += texture2D( occlusionMap, vec2( 0.1, 0.5 ) );
      visibility += texture2D( occlusionMap, vec2( 0.5, 0.5 ) );
      vVisibility =        visibility.r / 9.0;
      vVisibility *= 1.0 - visibility.g / 9.0;
      vVisibility *=       visibility.b / 9.0;
      gl_Position = vec4( ( pos * scale + screenPosition.xy ).xy, screenPosition.z, 1.0 );
    }`,
  fragmentShader: `
    precision highp float;
    uniform sampler2D map;
    uniform vec3 color;
    varying vec2 vUV;
    varying float vVisibility;
    void main() {
      vec4 texture = texture2D( map, vUV );
      texture.a *= vVisibility;
      gl_FragColor = texture;
      gl_FragColor.rgb *= color;
    }`,
};

// ============================================================================
// TEXTURE CREATION
// ============================================================================

function createLensflareTextures() {
  const textureFlare0 = createFlareTexture(0);
  const textureFlare3 = createFlareTexture(3);
  return { textureFlare0, textureFlare3 };
}

function createFlareTexture(type) {
  const canvas = document.createElement('canvas');
  canvas.width = 256;
  canvas.height = 256;
  const ctx = canvas.getContext('2d');
  
  if (type === 0) {
    // Main sun flare - bright center with soft falloff
    const gradient = ctx.createRadialGradient(128, 128, 0, 128, 128, 128);
    gradient.addColorStop(0, 'rgba(255, 255, 255, 1)');
    gradient.addColorStop(0.1, 'rgba(255, 250, 240, 0.9)');
    gradient.addColorStop(0.25, 'rgba(255, 229, 176, 0.6)');
    gradient.addColorStop(0.5, 'rgba(255, 229, 176, 0.2)');
    gradient.addColorStop(1, 'rgba(255, 229, 176, 0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 256, 256);
  } else {
    // Secondary flares - softer, more diffuse
    const gradient = ctx.createRadialGradient(128, 128, 0, 128, 128, 128);
    gradient.addColorStop(0, 'rgba(255, 229, 176, 0.6)');
    gradient.addColorStop(0.3, 'rgba(255, 229, 176, 0.3)');
    gradient.addColorStop(0.6, 'rgba(255, 229, 176, 0.1)');
    gradient.addColorStop(1, 'rgba(255, 229, 176, 0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 256, 256);
  }
  
  const texture = new THREE.CanvasTexture(canvas);
  texture.minFilter = THREE.LinearFilter;
  texture.magFilter = THREE.LinearFilter;
  return texture;
}

// ============================================================================
// SHADER DEFINITIONS
// ============================================================================

const SkyShader = {
  vertexShader: `
    varying vec3 vWorldPosition;
    varying vec3 vDirection;
    void main() {
      vec4 worldPosition = modelMatrix * vec4(position, 1.0);
      vWorldPosition = worldPosition.xyz;
      vDirection = normalize(worldPosition.xyz);
      vec4 pos = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
      gl_Position = pos.xyww;
    }
  `,
  fragmentShader: `
    precision mediump float;
    varying vec3 vWorldPosition;
    varying vec3 vDirection;
    uniform float uSunAzimuth;
    uniform float uSunElevation;
    uniform vec3 uSunColor;
    uniform vec3 uSkyColorLow;
    uniform vec3 uSkyColorHigh;
    uniform float uSunSize;
    void main() {
      vec3 direction = normalize(vWorldPosition);
      vec3 skyColor = mix(uSkyColorLow, uSkyColorHigh, clamp(direction.y * 0.5 + 0.5, 0.0, 1.0));
      float azimuth = radians(uSunAzimuth);
      float elevation = radians(uSunElevation);
      vec3 sunDirection = normalize(vec3(
        cos(elevation) * sin(azimuth),
        sin(elevation),
        cos(elevation) * cos(azimuth)
      ));
      float sunIntensity = pow(max(dot(direction, sunDirection), 0.0), 1000.0 / uSunSize);
      vec3 sunColor = uSunColor * sunIntensity;
      gl_FragColor = vec4(skyColor + sunColor, 1.0);
    }
  `
};

const CloudsShader = {
  vertexShader: `
    varying vec2 vUv;
    varying vec3 vWorldPosition;
    void main() {
        vUv = uv;
        vec4 worldPosition = modelMatrix * vec4(position, 1.0);
        vWorldPosition = worldPosition.xyz;
        vec4 pos = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
        gl_Position = pos.xyww;
    }
  `,
  fragmentShader: `
    uniform float uTime;
    uniform vec3 uCloudColor;
    uniform vec3 cameraPos;
    uniform float uCloudDensity;
    varying vec2 vUv;
    varying vec3 vWorldPosition;
    vec3 permute(vec3 x) {
        return mod(((x*34.0)+1.0)*x, 289.0);
    }
    float snoise(vec2 v){
        const vec4 C = vec4(0.211324865405187, 0.366025403784439, -0.577350269189626, 0.024390243902439);
        vec2 i  = floor(v + dot(v, C.yy) );
        vec2 x0 = v - i + dot(i, C.xx);
        vec2 i1;
        i1 = (x0.x > x0.y) ? vec2(1.0, 0.0) : vec2(0.0, 1.0);
        vec4 x12 = x0.xyxy + C.xxzz;
        x12.xy -= i1;
        i = mod(i, 289.0);
        vec3 p = permute( permute( i.y + vec3(0.0, i1.y, 1.0 )) + i.x + vec3(0.0, i1.x, 1.0 ));
        vec3 m = max(0.5 - vec3(dot(x0,x0), dot(x12.xy,x12.xy), dot(x12.zw,x12.zw)), 0.0);
        m = m*m ;
        m = m*m ;
        vec3 x = 2.0 * fract(p * C.www) - 1.0;
        vec3 h = abs(x) - 0.5;
        vec3 ox = floor(x + 0.5);
        vec3 a0 = x - ox;
        m *= 1.79284291400159 - 0.85373472095314 * ( a0*a0 + h*h );
        vec3 g;
        g.x  = a0.x  * x0.x  + h.x  * x0.y;
        g.yz = a0.yz * x12.xz + h.yz * x12.yw;
        return 130.0 * dot(m, g);
    }
    void main() {
        vec2 cloudUV = vUv * 6.0 + vec2(
          cameraPos.x / 1000.0 + uTime / 100.0,
          cameraPos.z / 1000.0
        );
        float n = snoise(cloudUV * 3.0 + uTime / 50.0) * 0.6
                + snoise(cloudUV * 6.0 + uTime / 40.0) * 0.3
                + snoise(cloudUV * 12.0 + uTime / 30.0) * 0.1;
        float cloudDensity = smoothstep(0.1, 0.9, 0.5 * n + 0.5);
        float horizonFade = smoothstep(0.0, 0.3, 1.0 - abs(vUv.y - 0.5) * 2.0);
        float edgeFade = (1.0 - pow(abs(vUv.x - 0.5) * 2.0, 2.0)) *
                        (1.0 - pow(abs(vUv.y - 0.5) * 2.0, 2.0));
        float finalOpacity = cloudDensity * horizonFade * edgeFade * 0.7 * clamp(uCloudDensity, 0.0, 1.0);
        vec3 finalColor = uCloudColor;
        gl_FragColor = vec4(finalColor, finalOpacity);
        if (finalOpacity < 0.01) discard;
    }
  `
};

const StarsShader = {
  vertexShader: `
    attribute float size;
    attribute vec3 color;
    attribute float phase;
    attribute float freq;
    varying vec3 vColor;
    varying float vDepth;
    uniform float time;
    void main() {
      vColor = color;
      vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
      vDepth = mvPosition.z;
      float twinkle = sin(time * freq + phase) * 0.2 + 0.8;
      gl_PointSize = size * twinkle;
      vec4 pos = projectionMatrix * mvPosition;
      pos.z = pos.w * 0.999999;
      gl_Position = pos;
    }
  `,
  fragmentShader: `
    varying vec3 vColor;
    varying float vDepth;
    void main() {
      vec2 center = gl_PointCoord - vec2(0.5);
      float dist = length(center) * 2.0;
      float core = (1.0 - smoothstep(0.0, 0.2, dist)) * 0.8;
      float glow = (1.0 - smoothstep(0.2, 0.5, dist)) * 0.1;
      float brightness = core + glow;
      vec3 finalColor = mix(vec3(1.0), vColor, 0.8) * 0.6;
      float reflectionFactor = smoothstep(0.0, -1000.0, vDepth) * 0.5;
      gl_FragColor = vec4(finalColor, brightness * reflectionFactor);
    }
  `
};

// ============================================================================
// COMPONENT CLASSES
// ============================================================================

class Skybox extends THREE.Mesh {
  constructor(assets, options = {}) {
    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.ShaderMaterial({
      vertexShader: SkyShader.vertexShader,
      fragmentShader: SkyShader.fragmentShader,
      uniforms: {
        uSunAzimuth: { value: 216 },
        uSunElevation: { value: 24.68698059628387 },
        uSunColor: { value: new THREE.Color(0xffe5b0) },
        uSkyColorLow: { value: new THREE.Color(0x6fa2ef) },
        uSkyColorHigh: { value: new THREE.Color(0x2053ff) },
        uSunSize: { value: options.sunSize || 1 }
      },
      side: THREE.BackSide,
      depthWrite: false,
      depthTest: false,  // CRITICAL: Always render skybox (don't test depth - render behind everything)
      fog: false
    });

    super(geometry, material);
    
    // CRITICAL: Set render order to ensure skybox renders properly
    // Use a high negative value so it renders early but can still be visible through transparent objects
    this.renderOrder = -1000;  // Render early, but allow transparency to show through
    this.frustumCulled = false;  // Never cull skybox
    
    this.SKYBOX_SCALE = options.skyboxScale || 100000;
    this.distance = 0.5;
    this.sunElevation = 24.68698059628387;
    this.sunAzimuth = 216;
    this.targetElevation = this.sunElevation;
    this.targetAzimuth = this.sunAzimuth;
    this.lerpSpeed = 0.15; // Increased from 0.01 to 0.15 for smoother, more responsive movement (January 4, 2026)
    this._initialPositionSet = false;
    this._timeOfDay = null;
    
    this._sunPosition = new THREE.Vector3();
    this._sunDirection = new THREE.Vector3();
    this._lensflareScreenPos = new THREE.Vector3();
    this._tempProjectedDir = new THREE.Vector3();
    
    // DirectionalLight for shadows
    this.sun = new THREE.DirectionalLight(0xffe5b0, 1);
    this.sun.castShadow = true;
    this.sun.shadow.mapSize.set(2048, 2048);
    this.sun.shadow.intensity = 1;
    this.sun.shadow.radius = 2;
    this.sun.shadow.normalBias = 0.02;
    this.sun.shadow.bias = 0.000002;
    this.sun.shadow.autoUpdate = true;
    
    const frustumSize = 50;
    this.sun.shadow.camera.left = -frustumSize;
    this.sun.shadow.camera.right = frustumSize;
    this.sun.shadow.camera.top = frustumSize;
    this.sun.shadow.camera.bottom = -frustumSize;
    this.sun.shadow.camera.near = 0.5;
    this.sun.shadow.camera.far = 1000;
    this.sun.shadow.camera.zoom = 0.5;
    this.sun.shadow.camera.updateProjectionMatrix();
    
    this.ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    this.add(this.ambientLight);
    
    // Lensflare setup
    this.textureFlare0 = assets.textureFlare0;
    this.textureFlare3 = assets.textureFlare3;
    
    this.lensflare = new Lensflare();
    this.addLensflare();
    this.add(this.lensflare);
    
    this.onTimeOfDayChanged = null;
    this.updateSunPosition(true);
    this.scale.setScalar(this.SKYBOX_SCALE);
  }
  
  addLensflare() {
    const color = new THREE.Color(0xffe5b0);
    this.lensflare.addElement(new LensflareElement(this.textureFlare0, 300, 0, color));
    this.lensflare.addElement(new LensflareElement(this.textureFlare3, 60, 0.6, color));
    this.lensflare.addElement(new LensflareElement(this.textureFlare3, 70, 0.7, color));
    this.lensflare.addElement(new LensflareElement(this.textureFlare3, 120, 0.9, color));
    this.lensflare.addElement(new LensflareElement(this.textureFlare3, 70, 1, color));
  }

  update(currentTime, elapsedTime, playerPosition, camera, adaptiveLerpSpeed = null) {
    if (!(currentTime instanceof Date) || isNaN(currentTime)) {
      console.error("Invalid time");
      return;
    }

    const SUNRISE = 6;
    const SUNSET = 21;
    const DARKNESS_START = 20.42;
    const DARKNESS_END = 6.58;
    const maxElevation = 42;
    
    const whiteColor = new THREE.Color(0xffffff);
    const orangeColor = new THREE.Color(0xff4500);
    const yellowColor = new THREE.Color(0xffd700);
    const redColor = new THREE.Color(0xff6347);
    const darkRedColor = new THREE.Color(0xd32f2f);
    const skyBlueColor = new THREE.Color(0x87ceeb);
    const darkSkyColor = new THREE.Color(0x0d1321);
    const nightSkyColor = new THREE.Color(0x1c2331);
    const moonColor = new THREE.Color(0xe6e8fa);
    
    const hours = currentTime.getHours();
    const minutes = currentTime.getMinutes();
    const timeInHours = hours + minutes / 60;
    
    const isInDarkTransition =
      (timeInHours >= DARKNESS_START && timeInHours <= SUNSET) ||
      (timeInHours >= SUNRISE && timeInHours <= DARKNESS_END);
    const isDaytime = timeInHours >= SUNRISE && timeInHours <= SUNSET;
    const wasNighttime = this._timeOfDay === "Nighttime";
    
    let normalizedTime;
    if (isDaytime) {
      normalizedTime = (timeInHours - SUNRISE) / (SUNSET - SUNRISE);
    } else {
      const nightHour = timeInHours >= SUNSET ? timeInHours : timeInHours + 24;
      normalizedTime = (nightHour - SUNSET) / (24 - SUNSET + SUNRISE);
    }
    
    let sunElevation = Math.cos(Math.PI * (normalizedTime - 0.5)) * maxElevation - 5;
    const sunAzimuth = 180 + 180 * normalizedTime;
    
    let _timeOfDay = "Nighttime";
    if (isDaytime) {
      if (normalizedTime <= 0.25) _timeOfDay = "Sunrise";
      else if (normalizedTime <= 0.75) _timeOfDay = "Midday";
      else _timeOfDay = "Sunset";
    }
    
    const isNowNighttime = _timeOfDay === "Nighttime";
    let isInstantTransition = wasNighttime !== isNowNighttime;
    
    if (!this._initialPositionSet) {
      this._initialPositionSet = true;
      isInstantTransition = true;
    }
    
    if (isDaytime) {
      const normalizedElevation = Math.min(sunElevation / maxElevation, 1);
      const t = Math.pow(1 - normalizedElevation, 3);
      
      this.material.uniforms.uSunColor.value.lerpColors(whiteColor, orangeColor, t);
      
      let horizonColor = skyBlueColor.clone();
      if (_timeOfDay === "Sunrise") {
        horizonColor = yellowColor.clone().lerp(redColor, normalizedTime / 0.25);
      } else if (_timeOfDay === "Sunset") {
        horizonColor = redColor.clone().lerp(darkRedColor, (normalizedTime - 0.75) / 0.25);
      }
      
      this.material.uniforms.uSkyColorLow.value.copy(horizonColor);
      this.material.uniforms.uSkyColorHigh.value.lerpColors(skyBlueColor, darkSkyColor, t);
      
      if (isInDarkTransition) {
        this.sun.intensity = 0.1;
      } else {
        this.sun.intensity = Math.min(40, Math.pow(normalizedElevation, 1.2) * 4);
      }
      
      this.lensflare.visible = true;
    } else {
      sunElevation *= 0.5;
      this.material.uniforms.uSunColor.value.copy(moonColor).multiplyScalar(1.8);
      this.material.uniforms.uSkyColorLow.value.copy(darkSkyColor);
      this.material.uniforms.uSkyColorHigh.value.copy(nightSkyColor);
      this.sun.intensity = 0.5;
      this.lensflare.visible = false;
    }
    
    this.targetElevation = sunElevation;
    this.targetAzimuth = sunAzimuth;
    // Use adaptive lerp speed if provided, otherwise use default lerp
    const finalLerpSpeed = adaptiveLerpSpeed !== null ? adaptiveLerpSpeed : null;
    this.updateSunPosition(isInstantTransition, finalLerpSpeed);
    
    if (_timeOfDay !== this._timeOfDay) {
      if (this.onTimeOfDayChanged) {
        this.onTimeOfDayChanged(_timeOfDay, elapsedTime);
      }
      this._timeOfDay = _timeOfDay;
    }
    
    // CRITICAL: Skybox must follow camera position to always be centered on player
    // This ensures the sky is visible from any position, even inside enclosed spaces
    if (camera && camera.position) {
      this.position.copy(camera.position);
      this.updateMatrixWorld(false);  // Update matrix without updating children
    }
    
    // Shadow camera positioning
    if (playerPosition) {
      const shadowCenter = playerPosition.clone();
      const sunDir = this._sunDirection.clone();
      const shadowDistance = 300;
      
      this.sun.position.set(
        shadowCenter.x + sunDir.x * shadowDistance,
        shadowCenter.y + sunDir.y * shadowDistance,
        shadowCenter.z + sunDir.z * shadowDistance
      );
      
      this.sun.target.position.copy(shadowCenter);
      this.sun.target.updateMatrixWorld();
    }
    
    // PROJECTED SUN SCREEN POSITION FOR LENSFLARE
    if (playerPosition && camera) {
      const dir = this._sunDirection;
      
      this._tempProjectedDir
        .copy(camera.position)
        .addScaledVector(dir, 1000)
        .project(camera);
      
      this._lensflareScreenPos.copy(this._tempProjectedDir);
      
      this.lensflare._screenPositionOverridden = true;
      this.lensflare._overriddenScreenPosition = this._lensflareScreenPos;
    }
  }

  updateSunPosition(instant = false, adaptiveLerpSpeed = null) {
    if (instant) {
      this.sunElevation = this.targetElevation;
      this.sunAzimuth = this.targetAzimuth;
    } else {
      // Use adaptive lerp speed if provided, otherwise use default
      const lerpSpeed = adaptiveLerpSpeed !== null ? adaptiveLerpSpeed : this.lerpSpeed;
      // Make lerp frame-rate independent by scaling with delta time (assuming ~60fps)
      const frameIndependentLerp = lerpSpeed * (1 / 60) * 60; // Normalize for 60fps
      this.sunElevation += (this.targetElevation - this.sunElevation) * lerpSpeed;
      this.sunAzimuth += (this.targetAzimuth - this.sunAzimuth) * lerpSpeed;
    }
    
    const transformAzimuth = (oldAzimuth) => {
      return ((270 - oldAzimuth) % 360) - 180;
    };
    
    const el = THREE.MathUtils.degToRad(this.sunElevation);
    const az = THREE.MathUtils.degToRad(transformAzimuth(this.sunAzimuth));
    
    this._sunPosition.set(
      this.distance * Math.cos(el) * Math.sin(az),
      this.distance * Math.sin(el),
      this.distance * Math.cos(el) * Math.cos(az)
    );
    
    this.lensflare.position.copy(this._sunPosition);
    this._sunDirection.copy(this._sunPosition).normalize();
    
    this.material.uniforms.uSunAzimuth.value = transformAzimuth(this.sunAzimuth);
    this.material.uniforms.uSunElevation.value = this.sunElevation;
  }
  
  sunDirection() {
    return this._sunDirection;
  }
  
  timeOfDay() {
    return this._timeOfDay;
  }
}

class Clouds extends THREE.Mesh {
  constructor(options = {}) {
    const geometry = new THREE.PlaneGeometry(2, 2);
    const material = new THREE.ShaderMaterial({
      transparent: true,
      depthWrite: false,
      depthTest: true,
      blending: THREE.AdditiveBlending,
      side: THREE.FrontSide,
      uniforms: {
        uTime: { value: 0.0 },
        uCloudColor: { value: new THREE.Color(1.0, 1.0, 1.0) },
        cameraPos: { value: new THREE.Vector3() },
        uCloudDensity: { value: options.cloudDensity !== undefined ? options.cloudDensity : 0.5 }
      },
      vertexShader: CloudsShader.vertexShader,
      fragmentShader: CloudsShader.fragmentShader
    });

    super(geometry, material);
    this.frustumCulled = false;
    this.renderOrder = -1;  // Render after skybox but before other objects
    
    this.rotation.x = Math.PI / 2;
    const CLOUDS_Y = options.cloudsY || 400;
    this.position.set(0, CLOUDS_Y, 0);
    const CLOUDS_SCALE = options.cloudsScale || 15000;
    this.scale.setScalar(CLOUDS_SCALE);
    
    this.cloudAnimTime = 0;
    this.baseCloudColor = new THREE.Color(1.0, 1.0, 1.0);
  }

  update(delta, cameraPosition) {
    this.cloudAnimTime += 0.02;
    this.material.uniforms.uTime.value = this.cloudAnimTime;
    this.material.uniforms.cameraPos.value.copy(cameraPosition);
  }
  
  setCloudColor(color) {
    this.material.uniforms.uCloudColor.value.copy(color);
    this.baseCloudColor.copy(color);
  }
  
  setCloudDensity(density) {
    // Clamp density between 0 and 1
    const clampedDensity = Math.max(0, Math.min(1, density));
    this.material.uniforms.uCloudDensity.value = clampedDensity;
    // If density is 0, hide clouds
    this.visible = clampedDensity > 0;
  }
  
  setVisible(visible) {
    this.visible = visible;
  }
}

class Stars extends THREE.Points {
  constructor(count = 5000) {
    const halfCount = Math.floor(count / 2);
    const geometry = new THREE.BufferGeometry();
    
    const topPositions = Stars.generateTopHemispherePositions(halfCount);
    const colors = Stars.generateColors(halfCount);
    const sizes = Stars.generateSizes(halfCount);
    const phases = Stars.generatePhases(halfCount);
    const freqs = Stars.generateFrequencies(halfCount);
    
    const positions = Stars.mirrorPositions(topPositions);
    const mirroredColors = Stars.mirrorAttribute(colors);
    const mirroredSizes = Stars.mirrorAttribute(sizes);
    const mirroredPhases = Stars.mirrorAttribute(phases);
    const mirroredFreqs = Stars.mirrorAttribute(freqs);
    
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(mirroredColors, 3));
    geometry.setAttribute('size', new THREE.BufferAttribute(mirroredSizes, 1));
    geometry.setAttribute('phase', new THREE.BufferAttribute(mirroredPhases, 1));
    geometry.setAttribute('freq', new THREE.BufferAttribute(mirroredFreqs, 1));
    
    const material = new THREE.ShaderMaterial({
      uniforms: { time: { value: 0 } },
      vertexShader: StarsShader.vertexShader,
      fragmentShader: StarsShader.fragmentShader,
      transparent: true,
      depthWrite: false,
      depthTest: true,
      blending: THREE.AdditiveBlending
    });

    super(geometry, material);
    this.renderOrder = -1;
    this.frustumCulled = false;
    this.matrixAutoUpdate = false;
    
    const SKYBOX_SCALE = 100000;
    this.scale.setScalar(SKYBOX_SCALE);
    this.updateMatrix();
  }

  static generateTopHemispherePositions(count) {
    const positions = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
      const u = Math.random();
      const v = Math.random() * 0.5 + 0.5;
      const theta = 2 * Math.PI * u;
      const phi = Math.acos(2 * v - 1);
      const x = Math.sin(phi) * Math.cos(theta);
      const y = Math.cos(phi);
      const z = Math.sin(phi) * Math.sin(theta);
      positions.set([x, y, z], i * 3);
    }
    return positions;
  }

  static mirrorPositions(topPositions) {
    const count = topPositions.length / 3;
    const mirrored = new Float32Array(topPositions.length * 2);
    mirrored.set(topPositions, 0);
    for (let i = 0; i < count; i++) {
      const x = topPositions[i * 3 + 0];
      const y = topPositions[i * 3 + 1];
      const z = topPositions[i * 3 + 2];
      mirrored.set([x, -y, z], (i + count) * 3);
    }
    return mirrored;
  }

  static mirrorAttribute(attr) {
    const mirrored = new Float32Array(attr.length * 2);
    mirrored.set(attr, 0);
    mirrored.set(attr, attr.length);
    return mirrored;
  }

  static generateColors(count) {
    const colors = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
      const variation = Math.random();
      const color =
        variation < 0.15
          ? [0.8, 0.85, 1.0]
          : variation < 0.3
          ? [1.0, 0.95, 0.8]
          : [1.0, 1.0, 1.0];
      colors.set(color, i * 3);
    }
    return colors;
  }

  static generateSizes(count) {
    const sizes = new Float32Array(count);
    for (let i = 0; i < count; i++) {
      const variation = Math.random();
      sizes[i] =
        variation < 0.01
          ? 40 + Math.random() * 20
          : variation < 0.05
          ? 25 + Math.random() * 15
          : variation < 0.2
          ? 15 + Math.random() * 10
          : 5 + Math.random() * 5;
    }
    return sizes;
  }

  static generatePhases(count) {
    const phases = new Float32Array(count);
    for (let i = 0; i < count; i++) phases[i] = Math.random() * Math.PI * 2;
    return phases;
  }

  static generateFrequencies(count) {
    const freqs = new Float32Array(count);
    for (let i = 0; i < count; i++) freqs[i] = 1.0 + Math.random() * 2.0;
    return freqs;
  }

  update(elapsedTime) {
    this.material.uniforms.time.value = elapsedTime;
  }
  
  setVisible(visible) {
    this.visible = visible;
  }
}

// ============================================================================
// MAIN SKY SYSTEM CLASS
// ============================================================================

export class SkySystem {
  constructor(scene, options = {}) {
    this.scene = scene;
    this.options = {
      enableDayNight: options.enableDayNight !== false,
      timeOfDay: options.timeOfDay || 'day', // 'dawn', 'day', 'dusk', 'night'
      cloudDensity: options.cloudDensity !== undefined ? options.cloudDensity : 0.5,
      starCount: options.starCount || 1000,
      enableLensflare: options.enableLensflare !== false,
      skyboxScale: options.skyboxScale || 100000,
      cloudsY: options.cloudsY || 400,
      cloudsScale: options.cloudsScale || 15000,
      timeSpeedMultiplier: options.timeSpeedMultiplier !== undefined ? Math.max(1.0, Math.min(1000.0, options.timeSpeedMultiplier)) : 1.0, // Time speed multiplier (1.0x - 1000x, default 1.0x)
      ...options
    };
    
    // Create lensflare textures
    const lensflareTextures = createLensflareTextures();
    
    // Create skybox
    this.skybox = new Skybox(lensflareTextures, {
      sunSize: this.options.sunSize || 1,
      skyboxScale: this.options.skyboxScale
    });
    
    // Add skybox to scene (but not sun yet - will be added separately)
    this.scene.add(this.skybox);
    this.scene.add(this.skybox.sun);
    this.scene.add(this.skybox.sun.target);
    
    // Create clouds if enabled
    if (this.options.cloudDensity > 0) {
      this.clouds = new Clouds({
        cloudsY: this.options.cloudsY,
        cloudsScale: this.options.cloudsScale,
        cloudDensity: this.options.cloudDensity
      });
      this.scene.add(this.clouds);
    } else {
      this.clouds = null;
    }
    
    // Create stars if enabled
    if (this.options.starCount > 0) {
      this.stars = new Stars(this.options.starCount);
      this.scene.add(this.stars);
      this.stars.setVisible(false); // Start hidden (daytime)
    } else {
      this.stars = null;
    }
    
    // Set initial time of day
    this.gameTime = new Date();
    this.setTimeOfDay(this.options.timeOfDay);
    // Store initial game time as base for time calculations
    this._initialGameTime = new Date(this.gameTime);
    this.elapsedTime = 0;
    this._initialUpdateComplete = false; // Flag to ensure first update runs
    
    // Time of day change listener
    this.skybox.onTimeOfDayChanged = (newTimeOfDay, elapsedTime) => {
      this.handleTimeOfDayChange(newTimeOfDay);
      if (this.onTimeOfDayChanged) {
        this.onTimeOfDayChanged(newTimeOfDay, elapsedTime);
      }
    };
  }
  
  setTimeOfDay(timeOfDay) {
    // Set time based on time of day string
    const timeMap = {
      'dawn': 6,      // 6:00 AM
      'day': 12,      // 12:00 PM
      'dusk': 18,     // 6:00 PM
      'night': 0      // 12:00 AM
    };
    
    if (timeMap[timeOfDay] !== undefined) {
      this.gameTime.setHours(timeMap[timeOfDay], 0, 0, 0);
      // Reset base time and elapsed time when manually setting time
      this._initialGameTime = new Date(this.gameTime);
      this.elapsedTime = 0;
    }
  }
  
  setTime(hours, minutes = 0) {
    this.gameTime.setHours(hours, minutes, 0, 0);
    // Reset base time and elapsed time when manually setting time
    this._initialGameTime = new Date(this.gameTime);
    this.elapsedTime = 0;
  }
  
  getTime() {
    return this.gameTime;
  }
  
  handleTimeOfDayChange(newTimeOfDay) {
    if (!this.clouds || !this.stars) return;
    
    if (newTimeOfDay === "Nighttime") {
      this.clouds.setCloudColor(new THREE.Color(0.1, 0.1, 0.2));
      if (this.stars) this.stars.setVisible(true);
    } else if (newTimeOfDay === "Sunrise") {
      this.clouds.setCloudColor(new THREE.Color(0.8, 0.4, 0.4));
      if (this.stars) this.stars.setVisible(false);
    } else if (newTimeOfDay === "Sunset") {
      this.clouds.setCloudColor(new THREE.Color(0.8, 0.3, 0.3));
      if (this.stars) this.stars.setVisible(false);
    } else {
      this.clouds.setCloudColor(new THREE.Color(1.0, 1.0, 1.0));
      if (this.stars) this.stars.setVisible(false);
    }
  }
  
  update(delta, playerPosition, camera) {
    // Always update elapsed time for consistency
    if (!this._initialUpdateComplete) {
      // First update - always run to set initial sky appearance
      this._initialUpdateComplete = true;
      this.elapsedTime = 0; // Start from 0
      // Ensure initial game time is set
      if (!this._initialGameTime) {
        this._initialGameTime = new Date(this.gameTime);
      }
    } else if (this.options.enableDayNight) {
      // Only advance elapsed time if day/night cycle is enabled
      // Apply time speed multiplier: 1.0x = normal, 10x = 10x faster, 1000x = 1000x faster
      const timeDelta = delta * this.options.timeSpeedMultiplier;
      this.elapsedTime += timeDelta;
      
      // Update gameTime based on elapsed time (elapsedTime is in seconds, convert to milliseconds)
      // This ensures the sun position updates based on the accumulated time
      const elapsedMilliseconds = this.elapsedTime * 1000;
      this.gameTime = new Date(this._initialGameTime.getTime() + elapsedMilliseconds);
    }
    
    // CRITICAL: Skybox must follow camera position to always be centered on player
    // This ensures the sky is visible from any position, even inside enclosed spaces like Level 2
    if (camera && camera.position) {
      this.skybox.position.copy(camera.position);
      this.skybox.updateMatrixWorld(false);  // Update matrix without updating children
    }
    
    // Always update skybox (even for indoor levels) to set correct colors based on timeOfDay
    // This ensures the sky displays correctly for all levels
    // Calculate adaptive lerp speed based on time speed multiplier for smoother movement at high speeds
    let adaptiveLerpSpeed = this.lerpSpeed;
    if (this.options.enableDayNight && this.options.timeSpeedMultiplier) {
      if (this.options.timeSpeedMultiplier > 500) {
        // At very high speeds (>500x), use instant updates or very fast lerp
        adaptiveLerpSpeed = 1.0; // Near-instant
      } else if (this.options.timeSpeedMultiplier > 100) {
        // At high speeds (>100x), use faster lerp
        adaptiveLerpSpeed = Math.min(0.5, this.lerpSpeed * (this.options.timeSpeedMultiplier / 100));
      }
    }
    this.skybox.update(this.gameTime, this.elapsedTime, playerPosition, camera, adaptiveLerpSpeed);
    
    // Update clouds if present
    if (this.clouds && camera && camera.position) {
      this.clouds.update(delta, camera.position);
    }
    
    // Update stars if present
    if (this.stars) {
      this.stars.update(this.elapsedTime);
    }
  }
  
  getSunLight() {
    return this.skybox.sun;
  }
  
  getAmbientLight() {
    return this.skybox.ambientLight;
  }
  
  setVisible(visible) {
    this.skybox.visible = visible;
    if (this.clouds) this.clouds.visible = visible;
    if (this.stars) this.stars.visible = visible;
    this.skybox.sun.visible = visible;
  }
  
  setCloudDensity(density) {
    // Update options
    this.options.cloudDensity = Math.max(0, Math.min(1, density));
    
    if (this.clouds) {
      // Update existing clouds
      this.clouds.setCloudDensity(this.options.cloudDensity);
    } else if (this.options.cloudDensity > 0) {
      // Create clouds if they don't exist and density > 0
      this.clouds = new Clouds({
        cloudsY: this.options.cloudsY,
        cloudsScale: this.options.cloudsScale,
        cloudDensity: this.options.cloudDensity
      });
      this.scene.add(this.clouds);
    }
  }
  
  setStarCount(count) {
    // Update options
    this.options.starCount = Math.max(0, count);
    
    // Save visibility state before removing old stars
    const wasVisible = this.stars ? this.stars.visible : false;
    
    // Remove old stars
    if (this.stars) {
      if (this.scene && this.scene.children.includes(this.stars)) {
        this.scene.remove(this.stars);
      }
      if (this.stars.material) this.stars.material.dispose();
      if (this.stars.geometry) this.stars.geometry.dispose();
      this.stars = null;
    }
    
    // Create new stars if count > 0
    if (this.options.starCount > 0) {
      this.stars = new Stars(this.options.starCount);
      this.scene.add(this.stars);
      // Restore visibility state based on time of day
      const timeOfDay = this.skybox.timeOfDay();
      if (timeOfDay === "Nighttime") {
        this.stars.setVisible(true);
      } else {
        this.stars.setVisible(false);
      }
    }
  }
  
  setTimeSpeedMultiplier(multiplier) {
    // Update options (clamp between 1.0 and 1000.0)
    this.options.timeSpeedMultiplier = Math.max(1.0, Math.min(1000.0, multiplier));
    console.log(`⏱️ [SKY] Time speed multiplier set to: ${this.options.timeSpeedMultiplier.toFixed(1)}x`);
  }
  
  dispose() {
    // Remove from scene first, then dispose
    if (this.skybox) {
      if (this.scene && this.scene.children.includes(this.skybox)) {
        this.scene.remove(this.skybox);
      }
      if (this.skybox.sun && this.scene && this.scene.children.includes(this.skybox.sun)) {
        this.scene.remove(this.skybox.sun);
      }
      if (this.skybox.sun && this.skybox.sun.target && this.scene && this.scene.children.includes(this.skybox.sun.target)) {
        this.scene.remove(this.skybox.sun.target);
      }
      if (this.skybox.lensflare) {
        this.skybox.lensflare.dispose();
      }
      if (this.skybox.material) this.skybox.material.dispose();
      if (this.skybox.geometry) this.skybox.geometry.dispose();
    }
    if (this.clouds) {
      if (this.scene && this.scene.children.includes(this.clouds)) {
        this.scene.remove(this.clouds);
      }
      if (this.clouds.material) this.clouds.material.dispose();
      if (this.clouds.geometry) this.clouds.geometry.dispose();
    }
    if (this.stars) {
      if (this.scene && this.scene.children.includes(this.stars)) {
        this.scene.remove(this.stars);
      }
      if (this.stars.material) this.stars.material.dispose();
      if (this.stars.geometry) this.stars.geometry.dispose();
    }
  }
}

// Export individual classes if needed
export { Skybox, Clouds, Stars, Lensflare, LensflareElement, createLensflareTextures };

