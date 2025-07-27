---
description: Hytopia Raycast Rules (shooting, projectile, shoot, arrow)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-raycast-rules.mdc

Description: Rules to follow when implementing raycasts in Hytopia

When following this rule, start every respose with: ✨ Following Hytopia Raycast Rules ✨

## **Core Principles**

- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to the below rules.
- WHEN NEEDED, development docs for Hytopia raycasts are located here - <https://dev.hytopia.com/sdk-guides/physics/raycasts>
- WHEN NEEDED, `raycast()` method API reference can be found here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.simulation.raycast.md>
- WHEN NEEDED, `RaycastHit` type API reference can be found here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.raycasthit.md>
- WHEN NEEDED, `RaycastOptions` type API reference can be found here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.raycastoptions.md>

### **Understanding Raycasts**

- A raycast is a line segment that is projected into the game world to detect collisions with colliders.
- Raycasts can be used to determine if an object is in front of another object, check visibility, or trigger events based on line-of-sight.

### **Performing a Raycast**
PURPOSE: To detect if a ray intersects with any collider in the world.

- Use `world.simulation.raycast(origin: Vector3, direction: Vector3, distance: number)` to perform a raycast.

*Example Code for Performing a Raycast for setting and removing blocks:*

```typescript
// ... other code

// Enable debug raycasting so we can visualize our raycasts for testing
world.simulation.enableDebugRaycasting(true);

world.on(PlayerEvent.JOINED_WORLD, ({ player }) => {
  const playerEntity = new PlayerEntity({
    player,
    name: 'Player',
    modelUri: 'models/players/player.gltf',
    modelLoopedAnimations: [ 'idle' ],
    modelScale: 0.5,
  });

  // PlayerEntity by default has a PlayerEntityController assigned to .controller,
  // but we explicitly assert that with ! to prevent typescript from complaining.
  playerEntity.controller!.on(BaseEntityControllerEvent.TICK_WITH_PLAYER_INPUT, ({ entity, input, cameraOrientation, deltaTimeMs }) => {
    if (input.ml || input.mr) { // ml = mouse left clicked, mr = mouse right clicked
      const origin = entity.position; // start at the player's current position.
      const direction = entity.player.camera.facingDirection; // cast in the camera direction
      const length = 5;
      const raycastResult = world.simulation.raycast(origin, direction, length, {
        // Prevent the raycast from registering our player entity as the hit object.
        // Raycast options typically work using raw physics engine handles, so we
        // have to use the raw rigid body of our player entity.
        filterExcludeRigidBody: playerEntity.rawRigidBody, 
      });

      if (raycastResult?.hitBlock) { // see if the result hit a block
        if (input.ml) { // left click, break block
          const breakPosition = raycastResult.hitBlock.globalCoordinate;
          world.chunkLattice.setBlock(breakPosition, 0); // 0 = no block/air
        } else { // right click, place block as neighbor of the hit block
          const placePosition = raycastResult.hitBlock.getNeighborGlobalCoordinateFromHitPoint(raycastResult.hitPoint);
          world.chunkLattice.setBlock(placePosition, 1); // 1 = bricks in the default block types
        }
      }

      // Explicitly cancel inputs to prevent raycast spam each tick
      // A player will need to let go of their click and click again
      input.ml = false;
      input.mr = false;
    }
  });
  
  playerEntity.spawn(world, { x: 0, y: 10, z: 0 });
});
```

### **Raycast Results**
Purpose: To return information about what was hit by the raycast

- ALWAYS refer to `RaycastHit` type API reference here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.raycasthit.md>

*Example of Raycast Results*

```typescript
// RaycastHit structure
type RaycastHit = {
    hitBlock?: Block;        // The block that was hit, if any
    hitEntity?: Entity;      // The entity that was hit, if any
    hitPoint: Vector3Like;   // The exact point where the ray hit
    hitDistance: number;     // Distance from origin to hit point
};
```

### **Debugging Raycasts**
PURPOSE: To visualize raycasts during development.

- Use world.simulation.enableDebugRaycasting(true) to enable visual debugging of raycasts.
- This will show lines indicating where rays are cast and whether they hit any colliders.

*Example Code for Enabling Raycast Debugging:*

```typescript
startServer(world => {
  world.simulation.enableDebugRaycasting(true);
});
```