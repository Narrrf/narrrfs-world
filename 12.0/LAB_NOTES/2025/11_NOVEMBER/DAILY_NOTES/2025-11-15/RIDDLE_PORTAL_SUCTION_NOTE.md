# Riddle Portal Suction Note — 2025-11-15

## Summary
- Tightened Level 1 portal completion so players must jump directly into the doorway (horizontal < 2.5u, vertical < 3u).
- Added suction radius (5u) with 18u/sec pull strength to gently drag the capsule toward the portal when nearby, preventing stalls.

## Technical Details
- Logic lives in `updateRiddle3()` after the existing portal visibility checks.
- Suction applies only when:
  - Portal exists, is visible, and Riddle #3 `step3Complete` is true.
  - Player is not already meeting the win condition.
  - Horizontal distance < 5u and vertical < 4u.
- Implementation:
  1. Compute `pullDir = portalPos - playerPos`, zero out Y, normalize.
  2. `playerVelocity.addScaledVector(pullDir, suctionStrength * delta);`
  3. Nudge capsule directly: `playerCollider.start/end += pullDir * delta * 1.2`.
- Final win gate now requires `horizontalDistance < 2.5` AND `verticalDistance < 3.0`.

## QA Notes
- Tested by walking slowly toward portal: suction kicks in around 5u and accelerates player into doorway.
- Jumping straight in still works instantly; suction prevents wobbling near the threshold.
- No impact on performance (simple vector math, no extra raycasts).

## Next Steps
- Optional VFX: add particle swirl or shader pulse when suction is active.
- Consider audio cue when within suction radius to reinforce feedback.

## Level 2 Shelf Check (2025-11-15)
- Confirmed Shelf 7 statue exists (Cactoro) after a fresh Level 2 rebuild; screenshot mismatch came from cached meshes.
- Documented that shelves 14/16/18/20/22/24 now use `rotationOffset: Math.PI` so the back aisle faces inward consistently.
- Introduced bonus walkway pads `B1`/`B2` (pad indices 2,3) with Demon + Captor statues and matching labels so the center lane no longer looks empty.
- Current import order: shelves 1-36 filled (25-30 extend the left aisle, 31-36 host the first Flying set) plus bonus pads B1/B2. All placements verified 2025-11-15 with `DEBUG_FORCE_LEVEL2_START` enabled for rapid QA; rebuild Level 2 before reporting missing statues.

