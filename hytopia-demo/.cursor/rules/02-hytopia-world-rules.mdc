---
description: Hytopia World Rules (manager, skybox)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-world-rules.mdc

Description: Rules to follow when working with worlds in Hytopia

When following this rule, start every response with: ✨ Following Hytopia World Rules ✨

## **Core Principles**

- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to the below rules.
- ALWAYS utilize the `startServer(world => {})` callback function for access to the default world.
- ALWAYS use `player.joinWorld()` to connect players to world instances within your server.
- WHEN NEEDED, development docs for Hytopia worlds are located here - <https://dev.hytopia.com/sdk-guides/worlds>
- WHEN NEEDED, API reference the `World` class is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.world.md>

## **Understanding Worlds**
- A World is a container for all controls related to gameplay, such as audio, entities, lighting, physics, chunk terrain, and the tick loop. When you start your server, a default world is automatically created and provided for you to use.

## **World Lifecycle and Player Management**
PURPOSE: To control when players enter and leave worlds

- Players automatically join the default world when they join your game.
- Use `player.joinWorld(someWorldInstance)` to programmatically join players to another World instance within your server.
- Use `player.leaveWorld()` to remove the player from the current world but keep them connected to the server.
- Utilize the `world.onPlayerJoin()` and `world.onPlayerLeave()` callbacks to handle custom logic when a player joins or leaves the world.

*Example Code for Handling Player Join:*

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
};
```
## **Accessing World Managers and Functionality**

- ALWAYS access the various managers and functionality required to control game behavior through the World instance properties.

- `world.audioManager`: The audio manager for the world.
- `world.blockTypeRegistry`: The block type registry for the world.
- `world.chatManager`: The chat manager for the world.
- `world.chunkLattice`: The chunk lattice for the world.
- `world.entityManager`: The entity manager for the world.
- `world.eventRouter`: The event router for the world.
- `world.lightManager`: The light manager for the world.
- `world.loop`: The world loop for the world.
- `world.sceneUIManager`: The scene UI manager for the world.
- `world.simulation`: The physics simulation for the world.

## **Customizing the Skybox**
PURPOSE: To customize the visual appearance of a world.

- Add cubemap images to an assets/cubemaps/skybox folder in your project.
- You can define `skyboxUri` property on the World