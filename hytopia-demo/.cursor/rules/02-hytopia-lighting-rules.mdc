---
description: Hytopia Lighting Rules (light, sun, sunlight, spotlight, flashlight)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-lighting-rules.mdc

Description: How to work with lighting in Hytopia 

When following this rule, start every respose with: ✨ Following Hytopia Lighting Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to these rules.
- ONLY implement what was explicitly requested by the user. NEVER do more than what the user requests without asking first.
- ALWAYS import the `Lighting` class from Hytopia when working with lighting
- WHEN NEEDED, development docs for Hytopia lighting are located here - <https://dev.hytopia.com/sdk-guides/lighting>
- WHEN NEEDED, the API reference for the Light class is located here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.light.md>
- WHEN NEEDEd, API reference for `LightEvent` is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.lightevent.md>

## **Understanding Lighting Types**
OVERVIEW: The Hytopia SDK supports several types of lighting, each with unique properties and uses:

- Ambient Light: The base lighting of the entire world, defining minimum brightness and color.
- Point Lights: Dynamic lights emitting from a fixed point in space, casting real-time shadows. Graphically expensive, use sparingly.
- Spot Lights: Dynamic lights emitting a cone-shaped spotlight from a point, casting real-time shadows. Graphically expensive, use sparingly.
- Sun Light (Directional Light): A single light source emitting from a set position, casting light across the entire world.

### **Managing Ambient Light**
PURPOSE: To control the base lighting of the entire world.

- Use world.setAmbientLightColor({ r: number, g: number, b: number }) to set the RGB color of the ambient light.
- Use world.setAmbientLightIntensity(intensity: number) to set the intensity of the ambient light (0 to 1+).
- WHEN NEEDED, development docs for ambient lighting are located here - <https://dev.hytopia.com/sdk-guides/lighting/ambient-light>

*Example Code for Setting Ambient Light Color and Intensity:*

```typescript
world.setAmbientLightColor({ r: 255, g: 0, b: 0 }); // very red ambient lighting
world.setAmbientLightIntensity(20); // Very over-exposed, ultra bright lighting
```

### **Managing Point Lights**
PURPOSE: To emit light of a specific color and intensity from a positional point in the world.

- FIRST - create a point lights as a Light instance.
- SECOND - spawn the light using light.spawn(world).
- Use point lights sparingly due to their performance cost.
- WHEN NEEDED, development docs for point lights are located here - <https://dev.hytopia.com/sdk-guides/lighting/point-lights>


*Example Code for Creating and Spawning a Point Light:*

```typescript
const light = new Light({
  color: { r: 255, g: 0, b: 0 }, // the lights color, defaults to 255,255,255
  intensity: 5, // the lights intensity, defaults to 1
  position: { x: 10, y: 20, z: -10 } // where the point light emits in the world
});

light.spawn(world);
```

#### **Attaching Point Lights to Entities**
PURPOSE: To make a point light follow an entity as it moves.

- Use the attachedToEntity property to specify the entity to follow.
- Use the offset property to specify an offset of the point light relative to the attached entity.

*Example Code for Attaching a Point Light to an Entity:*

```typescript
const light = new Light({
  attachedToEntity: playerEntity, // the entity to follow
  color: { r: 255, g: 0, b: 0 },
  intensity: 5,
  offset: { x: 0, y: 1, z: 0 }, // an offset of the pointlight relative to the attached entity
});

light.spawn(world);
```

### **Managing Spot Lights**
PURPOSE: To emit light in a cone shape from a position towards a target position or tracked entity.

- FIRST - create a point lights as a Light instance.
- SECOND - spawn the light using light.spawn(world).
- Use point lights sparingly due to their performance cost.
- WHEN NEEDED, development docs for spot lights are located here - https://dev.hytopia.com/sdk-guides/lighting/spot-lights

*Example Code for Creating and Spawning a Spot Light:*

```typescript
const light = new Light({
  type: LightType.SPOTLIGHT,
  angle: Math.PI / 8, // Spot light angle in radians
  color: { r: 255, g: 200, b: 200 }, // A pinkish color
  intensity: 40,
  penumbra: 0.5, // Spot light penumbra
  position: { x: 0, y: 15, z: 0 }, // Position the spot light shines from
  trackedPosition: { x: 0, y: 0, z: 0 }, // Position the spot light shines towards
});

light.spawn(world);
```

#### **Attaching Spot Lights to Entities**
PURPOSE: To make a spot light follow an entity and track another entity as they move.

- Use the attachedToEntity property to specify the entity the light should be attached to.
- Use the trackedEntity property to specify the entity the light should track.

*Example Code for Attaching a Spot Light to an Entity and Tracking Another:*

```typescript
const light = new Light({
  type: LightType.SPOTLIGHT,
  attachedToEntity: someEntity,
  angle: Math.PI / 8, // Spot light angle in radians
  intensity: 40,
  penumbra: 0.5, // Spot light penumbra
  trackedEntity: someOtherEntity,
});

light.spawn(world);
```

*Example Code for Creating a Spot Light at a Fixed Position and Tracking an Entity:*

```typescript
const light = new Light({
  type: LightType.SPOTLIGHT,
  position: { x: -7, y: 10, z: -11 },
  angle: Math.PI / 8, // Spot light angle in radians
  intensity: 40,
  penumbra: 0.5, // Spot light penumbra
  trackedEntity: someOtherEntity,
});

light.spawn(world);
```

### **Sun Light (Directional Light)**
PURPOSE: Mimicks the Sun by casting a light across the entire world relative to its position with an intensity and color you can control.

- Sun light is controlled through a World instance.
- The sun light's properties can be changed at any time during gameplay.
- WHEN NEEDED, development docs for sun lights (directional lights) are located here - <https://dev.hytopia.com/sdk-guides/lighting/sun-light-directional>
- WHEN NEEDED, the API reference for the World class is located here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.world.md>

*Example Code for Controlling Sun Light Properties:*

```typescript
world.setDirectionalLightColor({ r: 255, g: 0, b: 0 }); // A red sun light
world.setDirectionalLightIntensity(10); // Very bright lighting
world.setDirectionalLightPosition({ x: 500, y: 100, z: 200 });
```

*Example Code for Basic Day & Night Cycle:*

```typescript
// Create a day/night cycle (60 second period)
let time = 0;
const updateRate = world.simulation.timestepS * 8; // update lighting every 8 ticks
const cycleLengthS = 60; // 60 seconds
const CYCLE_STEP = updateRate / cycleLengthS; // 60 fps * 60 seconds
const SUN_ORBIT_RADIUS = 100;

setInterval(() => {
  time = (time + CYCLE_STEP) % 1;
  const angle = time * Math.PI * 2;

  // Sun position
  const sunY = Math.sin(angle) * SUN_ORBIT_RADIUS;
  const sunZ = Math.cos(angle) * SUN_ORBIT_RADIUS;

  // Smooth transition factor (0 to 1)
  const dayProgress = (Math.sin(angle) + 1) / 2; // Normalized to 0-1 range for smooth transitions

  // Interpolate light intensities
  const directionalIntensity = 0.1 + (dayProgress * 0.7); // Smoothly varies between 0.1 and 0.8
  const ambientIntensity = 0.2 + (dayProgress * 0.8); // Smoothly varies between 0.2 and 1

  // Interpolate colors
  const directionalColor = {
    r: 64 + (dayProgress * 191), // 64 to 255
    g: 64 + (dayProgress * 191), // 64 to 255
    b: 128 + (dayProgress * 127), // 128 to 255
  };

  const ambientColor = {
    r: 40 + (dayProgress * 215), // 40 to 255
    g: 40 + (dayProgress * 215), // 40 to 255
    b: 80 + (dayProgress * 175), // 80 to 255
  };

  // Update lighting
  world.setDirectionalLightPosition({ x: 0, y: sunY, z: sunZ });
  world.setDirectionalLightIntensity(directionalIntensity);
  world.setDirectionalLightColor(directionalColor);
  world.setAmbientLightIntensity(ambientIntensity);
  world.setAmbientLightColor(ambientColor);
}, updateRate * 1000);
```

### **Using the Light Manager**
PURPOSE: To efficiently manage and retrieve dynamic lights (point lights and spot lights).

- Access the LightManager through world.lightManager.
- Use world.lightManager.getAllLights() to get an array of all loaded light instances in the world.
- Use world.lightManager.getAllEntityAttachedLights(entity: Entity) to get an array of all light instances attached to a specific entity.
- WHEN NEEDED, development docs for the light manager are located here - <https://dev.hytopia.com/sdk-guides/lighting/light-manager>.


*Example Code for Accessing the Light Manager:*

```typescript
const allLights = world.lightManager.getAllLights();
const entityLights = world.lightManager.getAllEntityAttachedLights(myEntity);
```
