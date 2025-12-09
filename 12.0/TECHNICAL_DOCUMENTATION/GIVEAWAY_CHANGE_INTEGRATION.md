# 🎁 Giveaway Change Command - Integration Guide

## Overview
This guide explains how to integrate the new `/giveaway-change` command into your Discord bot.

## Features Implemented

### 1. `/giveaway-change` Command ✅
- Interactive dropdown menu showing all active giveaways
- Displays prize, time remaining, winner count, and giveaway ID
- Admin-only command (requires Manage Messages permission)

### 2. Modal-Based Parameter Editing ✅
- Change giveaway duration (in hours)
- Change prize description
- Change winner count
- Change or add comments
- All changes are optional - only changed fields are updated

### 3. Enhanced Winner Notifications ✅
- Rich embed DMs sent to winners (like Twitter missions)
- Includes prize, position, timestamp, giveaway ID
- Professional appearance with trophy icon
- Clear next steps for winners

## Integration Steps

### Step 1: Add Command to Bot
The command file is already created: `discord/commands/giveaway-change.js`

Your bot should automatically load it if you're using a command loader that reads the `discord/commands/` directory.

### Step 2: Add Interaction Handlers to index.js

Add these handlers to your main bot file (usually `discord/index.js`):

```javascript
// Import the giveaway-change handlers
const giveawayChange = require('./commands/giveaway-change.js');

// Add to your interactionCreate event handler

client.on('interactionCreate', async interaction => {
    // ... existing code ...

    // Handle dropdown selection for giveaway change
    if (interaction.isStringSelectMenu() && interaction.customId === 'select_giveaway_to_change') {
        await giveawayChange.handleGiveawaySelection(interaction, queryDb);
        return;
    }

    // Handle modal submission for giveaway parameter changes
    if (interaction.isModalSubmit() && interaction.customId.startsWith('change_giveaway_modal_')) {
        await giveawayChange.handleGiveawayChangeModal(interaction, queryDb, client);
        return;
    }

    // ... rest of your interaction handlers ...
});
```

### Step 3: Test the Command

1. **Run the command:**
   ```
   /giveaway-change
   ```

2. **You should see:**
   - An embed listing active giveaways
   - A dropdown menu to select a giveaway

3. **Select a giveaway:**
   - A modal form appears with current values pre-filled
   - Modify any fields you want to change
   - Submit the form

4. **Result:**
   - Database is updated immediately
   - Discord message refreshes automatically
   - You get a confirmation with change log

### Step 4: Verify Winner Notifications

Create a test giveaway and let it end (or force end it):

```
/giveaway create prize:Test Prize duration:1 winners:1
```

Wait for it to end or use:

```
/giveaway end giveaway_id:<id> draw_winners:true
```

**Winners will now receive:**
- A rich embed DM with all details
- Trophy icon and professional formatting
- Clear instructions on next steps

## Database Changes

**No database schema changes required!** ✅

The command uses existing tables:
- `tbl_giveaways` - For reading and updating giveaway data
- `tbl_giveaway_winners` - Already used for winner tracking

## Command Usage Examples

### Example 1: Change Duration
```
/giveaway-change
→ Select giveaway from dropdown
→ Change duration from 36 minutes to 36 hours
→ Submit
✅ Giveaway updated, message refreshed
```

### Example 2: Change Prize and Winner Count
```
/giveaway-change
→ Select giveaway
→ Change prize: "100 DSPOINC" → "200 DSPOINC + NFT"
→ Change winners: 1 → 3
→ Submit
✅ Both changes applied
```

### Example 3: Add Comment
```
/giveaway-change
→ Select giveaway
→ Add comment: "Special Christmas giveaway! 🎄"
→ Submit
✅ Comment added to giveaway embed
```

## Troubleshooting

### Issue: Dropdown shows no giveaways
**Solution:** Make sure you have active giveaways created with `/giveaway create`

### Issue: Modal doesn't appear
**Solution:** Check console logs for permission errors or interaction timeout

### Issue: Changes don't reflect in Discord
**Solution:** The message refresh might have failed. Use `/giveaway refresh giveaway_id:<id>` to manually refresh

### Issue: Winners don't receive DMs
**Solution:** 
- User has DMs disabled
- Bot can't DM users with no mutual servers
- Check console logs for specific error messages

## Files Modified/Created

### Created:
- ✅ `discord/commands/giveaway-change.js` - Main command and handlers
- ✅ `discord/commands/GIVEAWAY_CHANGE_INTEGRATION.md` - This guide

### Modified:
- ✅ `discord/commands/giveaway.js` - Enhanced winner DM notifications, exported updateGiveawayMessage

### To Modify:
- ⏳ `discord/index.js` - Add interaction handlers (see Step 2 above)

## Security Notes

- ✅ Command requires `ManageMessages` permission
- ✅ Dropdown and modal are ephemeral (only visible to command user)
- ✅ Change log is created for all modifications
- ✅ All changes are validated before database update

## Performance Notes

- Dropdown limited to 25 most recent active giveaways (Discord limit)
- Database queries are optimized with proper indexes
- Message refresh happens asynchronously to avoid blocking

## Future Enhancements

Potential features for future versions:

1. **Bulk Operations** - Change multiple giveaways at once
2. **Schedule Changes** - Schedule duration changes for future time
3. **Template System** - Save and load giveaway templates
4. **Role Requirement Editing** - Change role requirements after creation
5. **Participant Management** - Add/remove specific participants

## Support

If you encounter issues:

1. Check console logs for detailed error messages
2. Verify database table structure matches expectations
3. Ensure bot has proper permissions in Discord
4. Test with a simple giveaway first

## Changelog

### Version 1.0 (2025-12-08)
- ✅ Initial implementation
- ✅ Dropdown selection for active giveaways
- ✅ Modal-based parameter editing
- ✅ Enhanced winner DM notifications
- ✅ Automatic message refresh after changes
- ✅ Comprehensive change logging

---

**Ready to deploy!** 🚀

Follow the integration steps above to add this feature to your bot.

