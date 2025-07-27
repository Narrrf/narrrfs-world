---
description: Hytopia Input and Control Rules (mouse click, left click, right click, keystroke, key, clicked)
globs: 
alwaysApply: false
---
Rule Name: 02-hytopia-input-and-control-rules.mdc

Description: Rules to follow when working with player inputs and controls in Hytopia

When following this rule, start every response with: ✨ Following Hytopia Input and Control Rules ✨

## **Core Principles**

- ALWAYS fetch and consider [01-hytopia-global-rules.mdc](mdc:.cursor/rules/01-hytopia-global-rules.mdc) in addition to the below rules.
- ALWAYS utilize the onTickWithPlayerInput() method of an Entity Controller to process player inputs.
- ALWAYS import type `PlayerInput` from Hytopia
- ALWAYS access the current input state of any player through player.input and player.camera.orientation.
- WHEN NEEDED, development docs for Hytopia inputs and controls are located here - <https://dev.hytopia.com/sdk-guides/input-and-controls>
- FOR MORE ADVANCED ADJUSTMENTS to the entity controller, fetch and consider [02-hytopia-entity-controller-rules.mdc](mdc:.cursor/rules/02-hytopia-entity-controller-rules.mdc)

## **Available Inputs**
PURPOSE: To recognize and utilize different types of player inputs.

*The following inputs are available each tick:*

- w, a, s, d: Boolean (true when pressed, false when not) - Movement keys.

- ml: Boolean (true when pressed, false when not) - Mouse Left Click.

- mr: Boolean (true when pressed, false when not) - Mouse Right Click.

- pitch: Number (in radians) - Camera Orientation - Pitch.

- yaw: Number (in radians) - Camera Orientation - Yaw.

- sp: Boolean (true when pressed, false when not) - Spacebar.

- sh: Boolean (true when pressed, false when not) - Shift.

- q, e, r, f, z, x, c, v: Boolean (true when pressed, false when not).

- 1, 2, 3, 4, 5, 6, 7, 8, 9: Boolean (true when pressed, false when not) - Number keys.

## **Using Player Inputs**
PURPOSE: To control a PlayerEntity based on player inputs.

- Use the onTickWithPlayerInput() method in a custom EntityController.
- ALWAYS ensure that the controller is attached to a PlayerEntity or a class extending it.

*Example Code for Using Player Inputs in with the Default Entity Controller*

```typescript

   playerEntity.controller!.on(BaseEntityControllerEvent.TICK_WITH_PLAYER_INPUT, ({ entity, input, cameraOrientation, deltaTimeMs }) => {
    if (input.ml) { // if the input is left mouse
      console.log('left mouse clicked', playerEntity.id);
    }
   };

```

## **Cancelling Player Inputs**
PURPOSE: To override or prevent player inputs from being processed.

- Set the input key to false on the player input state.

*Example Code for Cancelling a Player Input:*

```typescript
// other code ...
player.input['sp'] = false; // cancel out the space bar
```

*Example Code for only allowing a single keystroke or click*

```typescript

// other code, likely in the onTickWithPlayerInput() method, or somewhere else ...

if (input.ml) { // the left mouse button is clicked
  // .. fire a bullet
  
  // cancel the input, the player will
  // need to depress and repress the mouse
  // button to fire another bullet, which is
  // the behavior we want.
  input.ml = false;
}
```