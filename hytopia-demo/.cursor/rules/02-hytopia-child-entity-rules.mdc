---
description: Hytopia Child Entity Rules (attach, items, pick up, picked up, node, sword, fishing pole, hat, cosmetic, gloves, boots, chest, suit, weapon, bow)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-child-entity-rules.mdc

Description: Rules to follow when creating child entities in Hytopia

ALWAYS start every respose with: ✨ Following Hytopia Child Entity Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to these rules.
- ALWAYS import `Entity` class from Hytopia.
- ALWAYS remember that child entities are purely visual and cannot have physical colliders.
- WHEN NEEDED, the API reference for the Entity class is located here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.entity.md>
- WHEN NEEDED, development docs for Hytopia child entities are located here - <https://dev.hytopia.com/sdk-guides/entities/child-entities>
- WHEN NEEDED, API reference for `EntityEvent` is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.entityevent.md>
- ALWAYS implement ONLY what was explicitly requested by the user


## **Understanding Child Entities**
- Child entities are entities attached to a parent entity, creating visual attachments.
- Child entities inherit most behaviors from standard entities but have certain limitations.
- Child entities are supported for both Block Entities and Model Entities.
- ALWAYS remember that colliders and physical collisions are NOT supported by child entities.
- All colliders of a child entity will be disabled when attached to a parent.
- When a child is removed from its parent, its colliders will automatically be re-enabled.

### **Parent Must Be Spawned First**
- ALWAYS ensure that the parent entity is spawned BEFORE the child entity's .spawn() method is called.

### **Isolated Kinematic Simulation**
- Child entities can have visual forces, translations, and rotations applied.
- Child entities are always positioned and rotated relative to their parent.
- Child entities are anchored to the center point of their parent model by default
- Use a specific named model node of the parent model using `parentNodeName` in the child entity's options to attach it to a specific node

### **Creating Child Entities**
PURPOSE: To attach entities as children to other entities for visual effects.

- Always use the `parent` and `parentNodeName` options when creating a child entity
- ALWAYS set the `modelUri` property to the path of your model relative to the assets folder.
- For example, if the gltf file is assets/models/items/sword.png, then your `modelUri` is models/items/sword.png
- NEVER create additional mass unless asked
- NEVER initiate chat commands unless asked

*Example Code for Attaching a Sword to a Player's Hand:*

```typescript
const playerEntity = new PlayerEntity({
  player,
  name: 'Player',
  modelUri: 'models/players/player.gltf',
  modelLoopedAnimations: [ 'idle' ],
  modelScale: 0.5,
});

// The parent, which is the player entity, must be spawned before the sword is spawned.
playerEntity.spawn(world, { x: 0, y: 10, z: 0 });

const swordChildEntity = new Entity({
  name: 'sword',
  modelUri: 'models/items/sword.gltf',
  parent: playerEntity,
  parentNodeName: 'hand_right_anchor', // attach it to the hand node of our parent model
});

swordChildEntity.spawn(
  world,
  { x: 0, y: 0.3, z: 0.5 }, // spawn with a position relative to the parent node
  { x: -Math.PI / 3, y: 0, z: 0, w: 1 } // spawn with a rotation
);
```

### **Nesting Child Entities**
- ALWAYS be mindful of performance when nesting child entities. Excessive nesting can impact gameplay.
- REMEMBER, child entities do not have colliders, only the parent Entity does.

*Example Code for Nesting a Fish on the End of a Sword:*

```typescript
const playerEntity = new PlayerEntity({
  player,
  name: 'Player',
  modelUri: 'models/players/player.gltf',
  modelLoopedAnimations: [ 'idle' ],
  modelScale: 0.5,
});

// The parent, which is the player entity, must be spawned before the sword is spawned.
playerEntity.spawn(world, { x: 0, y: 10, z: 0 });

const swordChildEntity = new Entity({
  name: 'sword',
  modelUri: 'models/items/sword.gltf', // you might not have this model by default
  parent: playerEntity,
  parentNodeName: 'hand_right_anchor', // attach it to the hand node of our parent model
});

swordChildEntity.spawn(
  world,
  { x: 0, y: 0.3, z: 0.5 }, // spawn with a position relative to the parent node
  { x: -Math.PI / 3, y: 0, z: 0, w: 1 } // spawn with a rotation
);

// Everything above this is the same code from our previous example.
// Create our fish as a child of our sword
const fishChildEntity = new Entity({
  name: 'fish',
  modelUri: 'models/items/fish.gltf', // you might now have this model by default.
  modelScale: 2,
  parent: swordChildEntity,
});

fishChildEntity.spawn(world, { x: 0, y: 1, z: 0.5 });
```

### **Attaching and Detaching Entities**

- ALWAYS use the `setParent()` function of the `Entity` class to attach and detach entities as children.

*Example Code for Attaching and Detaching a Sword on Collision:*

```typescript
const swordEntity = new Entity({
  name: 'Sword',
  modelUri: 'models/items/sword.gltf',
});

swordEntity.on(EntityEvent.ENTITY_COLLISION, ({ otherEntity, started }) => {
  if (started && otherEntity instanceof PlayerEntity) {
    swordEntity.setParent(otherEntity, 'hand_right_anchor', { x: 0, y: 0.2, z: 0.4 }, Quaternion.fromEuler(-90, 0, 0));
      
    setTimeout(() => {
      const facingDirection = otherEntity.directionFromRotation;
      const playerPosition = otherEntity.position;
      swordEntity.setParent(undefined, undefined, { 
        x: playerPosition.x + -facingDirection.x * 2, 
        y: playerPosition.y + 1, 
        z: playerPosition.z + -facingDirection.z * 2,
      });
      swordEntity.setLinearVelocity({ x: -facingDirection.x * 10, y: 10, z: -facingDirection.z * 10 });
    }, 5000);
  }
});

swordEntity.spawn(world, { x: 3, y: 10, z: 3 });
```

### **Visual Manipulation of Child Entities**

- Methods available to standard entities for manipulating visual position and rotation (e.g., applying velocities and forces) work the same way on child entities.

*Example Code for Rotating a Hat Using Angular Velocity:*

```typescript
const hatEntity = new Entity({
  name: 'wizardHat',
  modelUri: 'models/pumpkinhead.gltf',
  parent: playerEntity,
  parentNodeName: 'neck',
  rigidBodyOptions: {
    // Angular velocity is constant because external
    // physical forces do not effect children since
    // child entities are kinematic rigid bodies.
    angularVelocity: { x: 0, y: 1, z: 0 },
  },
});
hatEntity.spawn(world, { x: 0, y: 0.8, z: 0 }); // position relative to parent node
```

### **Finding Model Anchor Points**

- ALWAYS import the `ModelRegistry` class in the SDK to get a list of named nodes (anchor points) available for a model.
- Use `console.log(ModelRegistry.instance.getNodeNames('models/items/sword.gltf'))` to log an array of unique available node names for the model URI.

### **Common Player Entity Anchor Points**


arm_left_anchor	
arm_right_anchor	
back_anchor	B
foot_left_anchor	
foot_right_anchor	
hand_left_anchor	
hand_left_shield_anchor	
hand_right_anchor
hand_right_weapon_anchor
head_anchor	Head anchor
leg_left_anchor
leg_right_anchor
torso_anchor