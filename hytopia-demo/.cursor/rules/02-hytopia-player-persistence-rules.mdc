---
description: Hytopia Player Persistence Rules (save game, save game data)
globs: 
alwaysApply: false
---
Rule Name: 21-hytopia-persistence-rules.mdc

Description: Rules for implementing player and global data persistence in HYTOPIA games

When following this rule, start every response with: ✨ Following HYTOPIA Persistence Rules ✨

## **Core Principles**

- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) alongside these rules.
- ALWAYS use persistence to store player-specific data (e.g., inventory, progress) or global game state (e.g., leaderboards, world settings).
- WHEN NEEDED, `Player` class API reference is here <https://github.com/hytopiagg/sdk/blob/main/docs/server.player.md>
- WHEN NEEDED, Persistence Manager API Reference is here <https://github.com/hytopiagg/sdk/blob/main/docs/server.persistencemanager.md>

## **Player-Specific Persistence**

Methods:

- player.setPersistedData(key: string, value: any) – Stores arbitrary JSON-serializable data under a unique key.
- player.getPersistedData<T>(key: string): T | undefined – Retrieves data by key.

*Example Usage:*

```typescript
// Save player progress  
player.setPersistedData("inventory", { sword: "Legendary", coins: 150 });  
player.setPersistedData("level", 12);  

// Load on join  
world.on(PlayerEvent.JOINED_WORLD, ({ player }) => { 
  const level = player.getPersistedData<number>("level") || 1;  
  const inventory = player.getPersistedData<Inventory>("inventory") || defaultInventory;  
});  
```

## **Global Persistence (PersistenceManager)**

Methods:

- PersistenceManager.instance.setGlobalData(key: string, value: any)
- PersistenceManager.instance.getGlobalData<T>(key: string): T | undefined
- PersistenceManager.instance.deleteGlobalData(key: string)

*Example Usage:*

```typescript
// Store world highscores  
PersistenceManager.instance.setGlobalData("leaderboard", {  
  topPlayer: "Alice",  
  score: 9500  
});  

// Retrieve during game setup  
const leaderboard = PersistenceManager.instance.getGlobalData<Leaderboard>("leaderboard");  
```
