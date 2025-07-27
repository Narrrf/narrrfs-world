---
description: Hytopia Player and Player Manager Rules (player id, player username, get player)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-player-and-player-manager-rules.mdc

Description: Rules to follow when working with players and the player manager in Hytopia

When following this rule, start every respose with: ✨ Following Hytopia Player Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to these rules.
- The `Player` class is automatically created when a player joins your game and handled internally by HYTOPIA services
- ALWAYS utilize the world.onPlayerJoin() callback to handle custom logic when a player joins your game.
- ALWAYS use the PlayerManager to efficiently retrieve and iterate through connected players.
- ALWAYS use the `world.onPlayerJoin()` callback to instantiate a PlayerEntity and handle any other necessary game logic.
- WHEN NEEDED, development docs for Hytopia players are located here - <https://dev.hytopia.com/sdk-guides/players>
- WHEN NEEDED, development docs for the Player Manager are here - <https://dev.hytopia.com/sdk-guides/players/player-manager>
- WHEN NEEDED, API reference for `Player` class is here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.player.md>

## **Player Properties**
PURPOSE: To access and utilize a player's core information and interfaces.

- `player.id`: A globally unique identifier of the player. This ID is the player's public HYTOPIA account ID and will be consistent for any game they join.

- `player.username`: A globally unique username of the player. This username is the player's public HYTOPIA username and will be consistent for any game they join.

- `player.camera`: An instance of the PlayerCamera class, automatically assigned to each player.

- `player.input`: The current input state of the player for each given tick.

- `player.ui`: An instance of the PlayerUI class, automatically assigned to each player.

- `player.world`: The current world instance the player is in.

## **Player Methods**
PURPOSE: To control a player's experience within your game.

- `player.disconnect()`: Forces the player to disconnect from the game server.
- `player.joinWorld(someWorldInstance)`: Connects a player to a provided world instance.
- `player.leaveWorld()`: Removes the player from the current world they are in but keeps them connected to your game server.


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
});
```

## **Accessing The PlayerManager**
PURPOSE: To access the PlayerManager instance

- Access the player manager for your game using PlayerManager.instance.

### **Using The PlayerManager**
PURPOSE: To retrieve players connected to your game server.

- `PlayerManager.instance.getConnectedPlayers()`: Returns an array of all currently connected players for the game server.

- `PlayerManager.instance.getConnectedPlayerByUsername('someUsername')`: Returns a player instance if there is a currently connected player with the provided username. If no matching player is found, returns undefined.

- `PlayerManager.instance.getConnectedPlayersByWorld(someWorld)`: Returns an array of all players currently in the provided world.