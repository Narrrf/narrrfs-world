# 🐦 TWITTER REQUESTS CATEGORY - DISCORD SETUP

**Date:** November 1, 2025  
**Purpose:** Create dedicated category for Twitter mission verification tickets  
**Status:** 📋 **SETUP GUIDE**  

---

## 🎯 WHAT YOU NEED TO CREATE

### **Discord Category Structure:**

```
📁 ENGAGE (existing category with other channels)

📁 🎫-item-requests (existing - for /useitem tickets)
   └── ticket-narrrf-cheese-egg
   └── ticket-user2-vip-pass
   └── ... (item usage tickets)

📁 🐦-twitter-requests (NEW - for Twitter mission verification)
   └── twitter-narrrf-12345678
   └── twitter-user2-87654321
   └── ... (Twitter mission tickets)
```

---

## 📋 DISCORD SETUP STEPS

### **Step 1: Create Category**

1. Right-click in your Discord server's channel list
2. Click "Create Category"
3. Name: `🐦-twitter-requests`
4. Set permissions:
   - **@everyone:** ❌ View Channel (hidden from public)
   - **Narrrf's World Bot:** ✅ View Channel, Send Messages, Manage Channels
   - **Admin/Moderator roles:** ✅ View Channel, Send Messages

### **Step 2: Get Category ID**

1. Enable Developer Mode in Discord (Settings → Advanced → Developer Mode)
2. Right-click the `🐦-twitter-requests` category
3. Click "Copy ID"
4. You'll get something like: `1234567890123456789`

### **Step 3: Add to .env File**

Add this line to your `.env` file:

```env
TWITTER_REQUESTS_CATEGORY_ID=1234567890123456789
```

**Example .env structure:**
```env
DISCORD_BOT_SECRET=your_bot_token
DISCORD_CLIENT_ID=your_client_id
DISCORD_GUILD=your_guild_id
ITEM_REQUESTS_CATEGORY_ID=1111111111111111111
TWITTER_REQUESTS_CATEGORY_ID=2222222222222222222  # NEW!
```

---

## 🔄 FALLBACK BEHAVIOR

### **If category is not set:**
```javascript
const categoryId = process.env.TWITTER_REQUESTS_CATEGORY_ID || process.env.ITEM_REQUESTS_CATEGORY_ID;
```

**Fallback logic:**
- ✅ **Preferred:** Uses `TWITTER_REQUESTS_CATEGORY_ID` if set
- ⚠️ **Fallback:** Uses `ITEM_REQUESTS_CATEGORY_ID` if Twitter category not set
- **Result:** Bot works immediately, but tickets go to item category until you set it up

---

## 📊 CATEGORY COMPARISON

| Feature | 🎫-item-requests | 🐦-twitter-requests |
|---------|------------------|---------------------|
| **Purpose** | Item usage approval | Twitter mission verification |
| **Triggered By** | `/useitem` command | "Join Mission" button |
| **Ticket Format** | `ticket-{user}-{item}` | `twitter-{user}-{missionId}` |
| **Buttons** | Approve/Deny | Approve/Deny |
| **Reward Type** | Item consumed | $DSPOINC added |
| **Auto-Close** | 30 seconds | 30 seconds |

---

## ✅ BENEFITS OF SEPARATE CATEGORY

### **Organization:**
- ✅ Clear separation between item requests and Twitter verifications
- ✅ Easier for admins to find specific ticket types
- ✅ Better Discord server organization

### **Permissions:**
- ✅ Different admin teams can manage different categories
- ✅ Can assign specific roles to each category
- ✅ More granular access control

### **Scalability:**
- ✅ Future expansion (quest tickets, bug report tickets, etc.)
- ✅ Professional Discord structure
- ✅ Clean, organized server layout

---

## 🚀 QUICK SETUP (5 MINUTES)

**Do this once, works forever:**

1. Create category: `🐦-twitter-requests` (2 min)
2. Copy category ID (30 sec)
3. Add to `.env` file (30 sec)
4. Restart bot (10 sec)
5. Done! ✅

**After setup:**
- Item tickets → 🎫-item-requests
- Twitter tickets → 🐦-twitter-requests
- Perfect organization! 🎯

---

## 🧪 TESTING CHECKLIST

### **After Setup:**
- [ ] Create `🐦-twitter-requests` category in Discord
- [ ] Copy category ID
- [ ] Add to `.env` as `TWITTER_REQUESTS_CATEGORY_ID`
- [ ] Restart bot
- [ ] Join a Twitter mission
- [ ] Verify ticket appears in **Twitter category** (not item category)
- [ ] Test Approve button
- [ ] Test Deny button
- [ ] Verify ticket auto-closes

---

## 📝 ENVIRONMENT VARIABLES SUMMARY

**Your `.env` needs:**
```env
# Discord Bot Config
DISCORD_BOT_SECRET=...
DISCORD_CLIENT_ID=...
DISCORD_GUILD=...

# Ticket Categories
ITEM_REQUESTS_CATEGORY_ID=...          # For /useitem tickets
TWITTER_REQUESTS_CATEGORY_ID=...       # For Twitter mission tickets (NEW!)
```

---

## 🎯 CURRENT STATUS

**Code Status:**
- ✅ Bot code updated to use `TWITTER_REQUESTS_CATEGORY_ID`
- ✅ Fallback to item category if not set
- ✅ Works immediately without setup (uses fallback)

**Discord Status:**
- ⏳ Category needs to be created in Discord
- ⏳ ID needs to be added to `.env`
- ⏳ Bot needs restart to use new category

**Next Steps:**
1. Create Discord category
2. Add ID to `.env`
3. Restart bot
4. Test Twitter mission join!

---

**Setup Guide Ready!** 🚀  
**Estimated Time:** 5 minutes  
**Complexity:** Easy (one-time setup)

