---
description: Hytopia Chat and Chat Command Rules (Messages)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-chat-and-command-rules.mdc

Description: Rules to follow when working with chat and commands

When following this rule, start every respose with: ✨ Following Hytopia Chat and Command Rules ✨

## **Core Principles**
- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to the below rules.
- ALWAYS import `ChatManager` class from Hytopia
- ALWAYS use the `ChatManager` class and its methods as intended by the SDK developers
- WHEN NEEDED, development docs for Hytopia chats and commands are located here - <https://dev.hytopia.com/sdk-guides/chat-and-commands>
- WHEN NEEDED, the API reference for the ChatManager class is located here - <https://github.com/hytopiagg/sdk/blob/main/docs/server.chatmanager.md>
- WHEN NEEDED, the API reference for `ChatEvent` is located here <https://github.com/hytopiagg/sdk/blob/main/docs/server.chatevent.md>
- ALWAYS implement ONLY what was explicitly requested by the user

### **Accessing the Chat Manager**
- ALWAYS access the `ChatManager` singleton for a world using `world.chatManager`.

### **Sending Chat Messages**
PURPOSE: To send messages to players.

*Broadcast Messages and Example Code:* 
- Use `world.chatManager.sendBroadcastMessage(message, color?)` to send a message to all connected players in the world. 
- The `color` argument is optional and specifies the message color in hexadecimal format (e.g., `'00FF00'` for green).

```typescript  
  // Send a message every second to all connected players.
  setInterval(() => {
    world.chatManager.sendBroadcastMessage('Another second has passed!');
  }, 1000);

```


*Player Messages and Example Code* 
- Use `world.chatManager.sendPlayerMessage(player, message, color?)` to send a message to a specific player. 
- The `color` argument is optional and specifies the message color in hexadecimal format.

```typescript

startServer(world => {
  world.on(PlayerEvent.JOINED_WORLD, ({ player }) => {
    // only the player that just joined will receive this message
    world.chatManager.sendPlayerMessage(player, 'Welcome to our game!');    
    
    // Send another message to the player on the next line of chat,
    // providing a third hex color argument to change the message color
    world.chatManager.sendPlayerMessage(player, 'Have fun!', '00FF00');
  });
});

```

### **Handling Broadcast Messages**
PURPOSE: To execute code whenever a player or the game sends a broadcast message.

- ALWAYS use `world.chatManager.onBroadcastMessage` to specify a callback for anytime a message is sent by a player, or your game.
- `world.chatManager.onBroadcastMessage` takes the `player` (or `undefined` if sent by the game), `message`, and `color` as arguments.


*Example Code for Message Callbacks:*

```typescript
startServer(world => {
  world.chatManager.on(ChatEvent.BROADCAST_MESSAGE, ({ player, message, color }) => {
    if (player) {
      console.log(`Player ${player.username} sent a new public message: ${message}`);
    } else {
      console.log(`The game sent a new message public message: ${message}`);
    }
  });
});
```

### **Registering Chat Commands**
PURPOSE: To create custom commands that players can execute in chat.

- ALWAYS Use `world.chatManager.registerCommand(command, callback)` to register a new command.
- The `command` argument is the command name (e.g., `'/help'`, `'ban'`).  The leading `/` is optional but recommended.
- Only one callback can be registered to the same command at a time.
- The `callback` argument is a function that takes the `player`, an array of `args`, and the full `message` as arguments.

*Simple Example Code for Message Callbacks*

```typescript

world.chatManager.registerCommand('/ping', (player, args, message) => {
     world.chatManager.sendPlayerMessage(player, 'pong!');
});
```

*Example Code for using message callbacks to setup admin control*

```typescript
world.chatManager.registerCommand('/kick', (player, args, message) => {
  const admins = [ 'arkdev', 'testuser123' ];
  if (admins.includes(player.username)) {
    const targetUsername = args[0];
    const targetPlayer = world.playerManager.getConnectedPlayerByUsername(targetUsername);

    if (targetPlayer) {
      targetPlayer.disconnect();
    }
  }
});
```

### **Unregistering Chat Commands**
PURPOSE: To remove registered chat commands.

- ALWAYS Use `world.chatManager.unregisterCommand(command)` to unregister a command.

