# 🔗 Discord Token of Render System

## Overview
The Discord Token of Render System is a dynamic solution for managing Discord invite links across all HTML pages in Narrrf's World. Instead of manually updating Discord links in every file, you can now change all Discord links at once by updating a single configuration file.

## 🎯 How It Works

### 1. Central Configuration
All Discord invite codes are managed in `discord-config.js`:
```javascript
const DISCORD_CONFIG = {
    inviteCode: 'rHc4Jg5Q', // 🔧 UPDATE THIS TOKEN TO CHANGE ALL DISCORD LINKS
    // ... other configuration
};
```

### 2. Automatic Link Updates
When any page loads, the system automatically:
- Scans all `<a>` tags with Discord links
- Updates `onclick` handlers containing Discord links
- Updates inline JavaScript containing Discord links
- Replaces old invite codes with the current one

### 3. Real-time Updates
The system runs on both `DOMContentLoaded` and `window.load` events to catch dynamic content.

## 🚀 Quick Start

### To Change All Discord Links:
1. Open `discord-config.js`
2. Update the `inviteCode` value to your new Discord invite code
3. Save the file
4. All pages will automatically use the new invite code

### Example:
```javascript
// Change this line in discord-config.js
inviteCode: 'rHc4Jg5Q', // Old code
inviteCode: 'newInviteCode123', // New code
```

## 📁 Files Included

### Core System
- `discord-config.js` - Main configuration and logic
- `discord-test.html` - Test page to verify functionality

### Pages with Discord Token System
The following pages now include the Discord Token of Render system:
- `index.html`
- `experiment-x.html`
- `whitepaper.html`
- `whitepaper-pro.html`
- `profile.html`
- `Bingo.html`
- `project-updates.html`
- `faq.html`
- `get-roles.html`
- `finances.html`

## 🧪 Testing

### Test Page
Visit `discord-test.html` to:
- See current Discord configuration
- Test different invite codes
- View real-time link updates
- Monitor system status

### Manual Testing
1. Open any page with Discord links
2. Open browser console (F12)
3. Look for messages like:
   ```
   🎯 Discord Token of Render System v12.0 loaded
   🔗 Current Discord URL: https://discord.gg/rHc4Jg5Q
   🔄 Updated Discord link: https://discord.gg/SYd2FC2U → https://discord.gg/rHc4Jg5Q
   ```

## 🔧 Technical Details

### Supported Link Types
The system automatically updates:
- `<a href="https://discord.gg/OLDCODE">` links
- `onclick="window.open('https://discord.gg/OLDCODE')"` handlers
- `onclick="window.location.href='https://discord.gg/OLDCODE'"` handlers
- Inline JavaScript containing Discord URLs

### Regex Pattern
The system uses this regex to find and replace Discord invite codes:
```javascript
/discord\.gg\/[a-zA-Z0-9]+/g
```

### Performance
- Lightweight: ~3KB JavaScript file
- Fast: Runs on page load, minimal overhead
- Safe: Only updates Discord links, leaves other content untouched

## 🎮 Usage Examples

### Adding to New Pages
To add the Discord Token system to a new page, include this line in the `<head>` section:
```html
<script src="discord-config.js"></script>
```

### Programmatic Updates
You can also update the invite code programmatically:
```javascript
// Update to a new invite code
DISCORD_CONFIG.updateInviteCode('newCode123');

// Get current Discord URL
console.log(DISCORD_CONFIG.getCurrentUrl());
```

## 🔍 Troubleshooting

### Links Not Updating
1. Check browser console for errors
2. Verify `discord-config.js` is loaded
3. Ensure the page includes the script tag
4. Check if the Discord link format matches the expected pattern

### Console Messages
The system logs all updates to the console:
- ✅ Successful updates
- 🔄 Link replacements
- 🎯 System initialization

## 📊 Benefits

### Before (Manual Updates)
- Update 10+ HTML files manually
- Risk of missing some links
- Time-consuming process
- Easy to make mistakes

### After (Token System)
- Update 1 configuration file
- All links updated automatically
- Consistent across all pages
- Real-time updates

## 🔮 Future Enhancements

### Potential Features
- Environment variable support
- API-based configuration
- Role-based Discord links
- Analytics tracking
- A/B testing support

### Integration Ideas
- Connect to Discord API for automatic invite code validation
- Add admin panel for invite code management
- Implement invite code rotation
- Add usage analytics

## 📞 Support

If you encounter issues:
1. Check the test page: `discord-test.html`
2. Review browser console for error messages
3. Verify the invite code format is correct
4. Ensure all pages include the `discord-config.js` script

---

**🎯 Remember**: Just update the `inviteCode` in `discord-config.js` and all Discord links across your entire site will automatically update! 