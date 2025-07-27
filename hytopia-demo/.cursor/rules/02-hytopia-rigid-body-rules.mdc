---
description: Hytopia Rigid Body Rules (teleport, impulse, torque, velocity, force, rotation)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-rigid-body-rules.mdc

Description: Rules to follow when working with rigid bodies in Hytopia

When following this rule, start every respose with: ✨ Following Hytopia Rigid Body Rules ✨

## **Core Principles**

- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to the below rules.
- ALWAYS understand that an Entity is a RigidBody (the `Entity` class inherits from the `RigidBody` class).
- ALWAYS choose the appropriate `RigidBodyType` based on the desired behavior
- WHEN NEEDED, development docs for rigid bodies are here - <https://dev.hytopia.com/sdk-guides/physics/rigid-bodies>
- WHEN NEEDED, API reference for `RigidBody` is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.rigidbody.md>
- WHEN NEEDED, API ref for `EntityOptions` is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.entityoptions.md>
- WHEN NEEDED, API ref for `RigidBody` methods - <https://github.com/hytopiagg/sdk/blob/main/docs/server.rigidbody.md#methods>

## **RigidBodyType**
PURPOSE: To define how a rigid body behaves within the physics simulation.

- ALWAYS choose the appropriate RigidBodyType based on the desired behavior.

- `RigidBodyType.DYNAMIC (Default)`: Affected by all external forces, including collisions, gravity, etc.

- `RigidBodyType.FIXED`: Does not move or rotate from collisions or forces. Position and rotation can be set explicitly, but physical interactions have no effect.

- `RigidBodyType.KINEMATIC_POSITION`: Unaffected by external forces and collisions, but can affect other dynamic rigid bodies. Position and rotation are assigned each tick. Velocities are internally calculated.

- `RigidBodyType.KINEMATIC_VELOCITY`: Unaffected by external forces and collisions, but can affect other dynamic rigid bodies. Velocities are set directly and remain constant until explicitly changed.

## **Rigid Body Options**
PURPOSE: To configure the physical properties and behavior of a rigid body.

- ALWAYS define rigid body properties using `RigidBodyOptions` when creating an entity.
- NEVER MAKE ASSUMPTIONS about available properties
- Refer to the API Reference for available properties - <https://github.com/hytopiagg/sdk/blob/main/docs/server.rigidbody.md>

*Rigid Body Option Properties*

- `additionalMass?`: number (Optional) The additional mass of the rigid body.
- `angularDamping?`: number (Optional) The angular damping of the rigid body.
- `angularVelocity?`:` Vector3Like (Optional) The angular velocity of the rigid body.
- `ccdEnabled?`: boolean (Optional) Whether the rigid body has continuous collision detection enabled.
- `colliders?`: ColliderOptions[] (Optional) The colliders of the rigid body.
- `enabledPositions?`: Vector3Boolean (Optional) The enabled axes of positional movement of the rigid body.
- `enabledRotations?`: Vector3Boolean (Optional) The enabled axes of rotational movement of the rigid body.
- `gravityScale?`: number (Optional) The gravity scale of the rigid body.
- `linearDamping?`: number (Optional) The linear damping of the rigid body.
- `linearVelocity?`: Vector3Like (Optional) The linear velocity of the rigid body.
- `position?`: Vector3Like (Optional) The position of the rigid body.
- `rotation?`: QuaternionLike (Optional) The rotation of the rigid body.
- `type?`: RigidBodyType (Optional) The type of the rigid body.

*Example Code for Creating a Dynamic Rigid Body with Limited Movement:*

```typescript
const heavyBlock = new Entity({
  blockTextureUri: 'textures/stone_bricks.png',
  blockHalfExtents: { x: 0.5, y: 0.5, z: 0.5 },
  rigidBodyOptions: {
    type: RigidBodyType.DYNAMIC,
    additionalMass: 10,
    enabledPositions: { x: false, y: false, z: true },
    enabledRotations: { x: false, y: false, z: false },
  },
});
heavyBlock.spawn(world, { x: -4, y: 10, z: -1 });
```

## **Updating Rigid Body Properties**
PURPOSE: To dynamically modify a rigid body's properties at runtime.

- Use the various methods provided by the `RigidBody` class to apply forces, impulses, and torques, and to adjust velocities and other properties.

- NEVER MAKE ASSUMPTIONS about methods. Use API ref - <https://github.com/hytopiagg/sdk/blob/main/docs/server.rigidbody.md>

- `.applyImpulse(impulse: Vector3Like)`: Applies an impulse to the rigid body.
- `.addTorque(torque: Vector3Like)`: Adds a torque to the rigid body.
- `.setLinearVelocity(velocity: Vector3Like)`: Sets the linear velocity of the rigid body.
- `.setAngularVelocity(velocity: Vector3Like)`: Sets the angular velocity of the rigid body.
- `.setPosition(position)`: Sets the position of the rigid body.

- And many more - refer to the API reference. <https://github.com/hytopiagg/sdk/blob/main/docs/server.rigidbody.md>

*Example Code for Applying an Impulse:*

```typescript
// Multiplying the impulse value by the mass is equivalent to adding
// the same velocity to the entity's current velocity.
// velocityChange = impulse force * mass
myEntity.applyImpulse({ x: 0 , y: 10 * myEntity.mass , z: 0 });
```

## **Continuous Collision Detection (CCD)**
PURPOSE: To prevent "tunneling" when entities move at high speeds.

- Enable CCD on rigid bodies that are likely to experience high velocities or sudden velocity changes.
- Be mindful of the performance impact of CCD; enable it only when necessary.

*Example Code for Enabling CCD:*

```typescript
const cow = new Entity({
  name: 'cow',
  modelUri: 'models/cow.gltf',
  modelLoopedAnimations: [ 'idle' ],
  modelScale: 0.5,
  rigidBodyOptions: {
    ccdEnabled: true
  },
});
```