---
description: Hytopie Movement and Pathfinding Rules (npc movement, enemy movement, pathfinding)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-movement-and-pathfinding-rules.mdc

Description: Rules to follow when implementing pathfinding in Hytopia for NPCs

When following this rule, start every respose with: ✨ Following Hytopia Movement and Pathfinding Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to these rules.
- ALWAYS understand and utilize the building blocks available for movement and pathfinding: `SimpleEntityController`, `RigidBody`, and `ChunkLattice`.
- WHEN NEEDED, docs for Hytopia entity movement and pathfinding are located here - <https://dev.hytopia.com/sdk-guides/entities/movement-and-pathfinding>
- WHEN NEEDED, the API reference for `SimpleEntityController` is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.simpleentitycontroller.md>
- WHEN NEEDED, the API reference for the `RigidBody` class is located here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.rigidbody.md>

## **Important Building Blocks & Primitives**

SimpleEntityController
- ALWAYS consider `SimpleEntityController` for relatively simple pathfinding and movement logic.
- This controller implements basic movement (`.move()`) and facing (`.face()`) functions to create realistic movement behaviors with defined speeds.
- When needing to stop a SimpleEntityController's `.move()` calls, it is important to invoke the `.stop()` method on the `SimpleEntityController` instance.

### **Basic Waypoint Movement Example**
PURPOSE: To demonstrate basic entity movement through a series of waypoints.

- The SimpleEntityController will take the most direct path to the next target waypoint, colliding with and potentially being stopped by any terrain or entities in the way. Keep this in mind when designing the path.

*Example Code for Basic Waypoint Movement:*

```typescript
// ... other code

let targetWaypointIndex = 0;

const WAYPOINT_COORDINATES = [
  { x: -5, y: 1, z: -7 },
  { x: 15, y: 1, z: 10 },
  { x: 17, y: 1, z: -12 },
];

const cow = new Entity({
  controller: new SimpleEntityController(),
  modelUri: 'models/npcs/cow.gltf',
  modelScale: 0.7,
  modelLoopedAnimations: [ 'walk' ],
  modelAnimationsPlaybackRate: 1.6, // roughly match the animation speed to the move speed we'll use
  rigidBodyOptions: {
    enabledRotations: { x: false, y: true, z: false }, // prevent flipping over when moving
  },
});

// We want to face towards the target each tick, since our relative position
// to the target may change as we move from a previous waypoint to the next.
cow.on(EntityEvent.TICK, () => {
  if (targetWaypointIndex >= WAYPOINT_COORDINATES.length) {
    return; // reached final waypoint, no need to rotate
  }

  // continually face towards target as we move
  const controller = cow.controller as SimpleEntityController;
  const targetWaypoint = WAYPOINT_COORDINATES[targetWaypointIndex];
  controller.face(targetWaypoint, 5);
});

cow.spawn(world, { x: 0, y: 3, z: 0 });

// Pathfind to the next waypoint as we reach each waypoint
const pathfind = () => {
  if (targetWaypointIndex >= WAYPOINT_COORDINATES.length) {
    cow.stopModelAnimations(['walk']);
    cow.startModelLoopedAnimations(['idle']);

    return; // reached final waypoint, no need to pathfind
  }
      
  const controller = cow.controller as SimpleEntityController;
  const targetWaypoint = WAYPOINT_COORDINATES[targetWaypointIndex];
    
  // Makes the controlled entity, the cow, start moving towards the waypoint
  // It will automatically handle it's own internal tick for movement.
  controller.move(targetWaypoint, 3, {
    moveCompleteCallback: () => {
      // pathfind to next waypoint
      targetWaypointIndex++; 
      pathfind();
    },
    moveIgnoreAxes: { x: false, y: true, z: false }, // ignore our y position when considering if movement is complete 
  });
};

pathfind();
```.