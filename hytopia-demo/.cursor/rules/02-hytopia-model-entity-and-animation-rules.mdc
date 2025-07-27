---
description: Hytopia Model Entity and Animation Rules (player models, npcs, vehicles, animal, pet, enemy, gltf, animation)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-model-entity-and-animation-rules.mdc

Description: Rules to follow when using model entities amd animations in Hytopia

When following this rule, start every respose with: ✨ Following Hytopia Model Entity and Animation Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to these rules.
- ALWAYS import `Entity` class from Hytopia.
- WHEN NEEDED, development docs for Hytopia model entities are located here - <https://dev.hytopia.com/sdk-guides/entities/model-entities>
- WHEN NEEDED, development docs for Hytopia entity animations are located here - <https://dev.hytopia.com/sdk-guides/entities/animations>
- WHEN NEEDED, the API reference for the Entity class is located here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.entity.md>
- ALWAYS implement ONLY what was explicitly requested by the user

### **Creating Model Entities**
PURPOSE: To define and instantiate Model Entities

- ALWAYS set the `modelUri` property to the path of your .gltf file.
- ALWAYS set the `modelUri` relative to the assets folder (for example, models/zombie.gltf if the file is in assets/models folder)
- NEVER include the assets folder in the modelUri
- Adjust the entity's scale using the `modelScale` property.
- Set looped animations to play on spawn with the `modelLoopedAnimations` property.
- Rigid body and collider properties are set via the `rigidBodyOptions` property.

*Example Code for Creating Model Entities:*

```typescript

//Example Model Entity Creation
const skeletonEntity = new Entity({
    modelUri: 'models/npcs/skeleton.gltf',
    modelLoopedAnimations: [ 'idle' ],
    modelScale: 0.8,
    rigidBodyOptions: {
      enabledRotations: { x: false, y: true, z: false }, // Only allow rotations around Y axis (Yaw)
    },
  });

skeletonEntity.spawn(world, { x: 4, y: 2, z: 6 });
```

### **Starting and Stopping Animations**
PURPOSE: To control the in-game animation state of a model.

- Use `.startModelLoopedAnimations(animations: string[])` to start looped animations.
- Use `.startModelOneshotAnimations(animations: string[])` to start one-shot animations.
- Use `.stopModelAnimations(animations: string[])` to stop specific animations.

*Example Code for One-Shot Animation*

```typescript

  playerEntity.startModelOneshotAnimations([ 'dance', 'wave' ]);

```

*Example Code for Animation State Changes in a Character Controller* 

```typescript
// tickPlayerMovement is a method from BaseCharacterController
// it is called each tick when associated with an entity
// controlled by a player (PlayerEntity)
public tickPlayerMovement(inputState: PlayerInputState, orientationState: PlayerOrientationState, deltaTimeMs: number) {
  // current pressed keystate of the controlling
  // player of the entity has pressed this tick.
  const { w, a, s, d, sp, sh, ml } = inputState;
  // ... Other character controller logic
  if (this.isGrounded && (w || a || s || d)) {
    if (isRunning) {
      // Stop all animations that aren't run
      this.entity.stopModelAnimations(Array.from(this.entity.modelLoopedAnimations).filter(v => v !== 'run'));
      // Play run animation, if it's already playing it won't be restarted since it's looped
      this.entity.startModelLoopedAnimations([ 'run' ]);
    } else {
      // Stop all animations that aren't walk
      this.entity.stopModelAnimations(Array.from(this.entity.modelLoopedAnimations).filter(v => v !== 'walk'));
      // Play walk animation, if it's already playing it won't be restarted since it's looped
      this.entity.startModelLoopedAnimations([ 'walk' ]);

    } else {
      // Stop all animations that aren't idle
      this.entity.stopModelAnimations(Array.from(this.entity.modelLoopedAnimations).filter(v => v !== 'idle'));
      // Play idle animation, if it's already playing it won't be restarted since it's looped
      this.entity.startModelLoopedAnimations([ 'idle' ]);

    if (ml) { // player pressed the left mouse button
      // Play our animation named 'simple_interact' once./
      this.entity.startModelOneshotAnimations([ 'simple_interact' ]);

      // ... Other character controller logic
    }
  }
}
```