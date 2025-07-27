---
description: Hytopia Player Controlled Entity Rules (play as, new player)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-player-controlled-entity-rules.mdc

Description: Rules to follow when implementing the player controlled entity

When following this rule, start every respose with: ✨ Following Hytopia Player Controlled Entity Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to these rules.
- ALWAYS use the `PlayerEntity` class to represent entities controlled by a player.
- WHEN NEEDED, development docs for Hytopia player-controlled entities are here - <https://dev.hytopia.com/sdk-guides/entities/player-controlled-entities>
- WHEN NEEDED, API reference for `PlayerEntityController` class is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.playerentitycontroller.md>

## **Understanding Player-Controlled Entities**
- Player-controlled entities, represented by the PlayerEntity class, are entities controlled by a player's inputs.
- PlayerEntity can be either a Model Entity or a Block Entity.

### **Creating a PlayerEntity**
PURPOSE: To create a new player entity controlled by a player when they join the game.

- ALWAYS pass the player property in the options when creating a `PlayerEntity`.
- The player property specifies the player whose inputs will control the actions of this entity.
- The default `PlayerEntityController()` is assigned to this entity if a custom controller is not provided.

*Example Code for Creating a PlayerEntity:*

```typescript
world.on(PlayerEvent.JOINED_WORLD, ({ player }) => {
  const playerEntity = new PlayerEntity({
    // PlayerEntity accepts an additional property 
    // in its options, player, which is the player
    // who's inputs will control the actions of this
    // entity. The default PlayerEntityController()
    // is assigned to this entity, since we did not
    // override it by specifying the `controller: new MyCustomController()`
    // property option.
    player,
    name: 'Player',
    modelUri: 'models/players/player.gltf',
    modelLoopedAnimations: [ 'idle' ],
    modelScale: 0.5,
  });
  
  playerEntity.spawn(world, { x: 0, y: 10, z: 0 });
});
```