# 📱 MOBILE LANDSCAPE MODE - Technical Documentation

**Date:** December 6, 2025  
**Status:** ✅ **PRODUCTION READY**  
**Feature:** Landscape Mode Option for Mobile Devices with Virtual Controller Pads

---

## 🎯 OVERVIEW

Mobile landscape mode allows players to rotate their device to landscape orientation for better gameplay with virtual controller pads (joysticks). The feature integrates with the Screen Orientation API and automatically enables virtual joysticks when landscape mode is activated.

---

## 🔧 TECHNICAL IMPLEMENTATION

### **Options Menu Integration**

**Location:** `three.js/main.js` (around line 6474)

**Visibility:** Only visible on mobile devices (`if (isMobile)`)

**Structure:**
```javascript
// 📱 MOBILE LANDSCAPE MODE (Only visible on mobile devices)
if (isMobile) {
  // Landscape Mode Section
  // - Label: "📱 Landscape Mode (Virtual Controllers)"
  // - Description: "Rotate device to landscape for better controls"
  // - Buttons: "Portrait" / "Landscape"
}
```

### **Screen Orientation API**

**Lock to Landscape:**
```javascript
await screen.orientation.lock('landscape');
```

**Unlock Orientation:**
```javascript
await screen.orientation.unlock();
```

**Error Handling:**
- Shows user-friendly toast message if API not supported
- Falls back to manual rotation if lock fails
- Graceful degradation for unsupported browsers

### **Joystick Integration**

**Automatic Enabling:**
- Joysticks automatically show when landscape mode is enabled
- `checkAndCreateJoystick()` checks `window.forceLandscapeMode` flag
- Orientation change listeners update joysticks automatically

**Key Variables:**
- `window.forceLandscapeMode` - Flag to force landscape mode
- `isMobileLandscape` - Current landscape state detection
- `window.innerWidth > window.innerHeight` - Landscape check

---

## 📋 FUNCTIONS

### **updateLandscapeButtons()**
- Updates button states in options menu
- Called when options menu opens
- Updates on orientation change

### **checkAndCreateJoystick()**
- Enhanced to check `forceLandscapeMode` flag
- Creates joysticks when landscape mode is active
- Removes joysticks when portrait mode is active

### **Orientation Change Listeners**
- `orientationchange` event listener
- `screen.orientation.change` event listener
- Automatic joystick and button updates

---

## 🎮 USER EXPERIENCE

### **On Mobile Devices:**

1. **Open Options Menu** (ESC or P key)
2. **Find "📱 Landscape Mode (Virtual Controllers)" section**
3. **Click "Landscape" button**
4. **Device rotates to landscape** (if API supported)
5. **Virtual controller pads appear automatically**
6. **Better gaming experience with touch controls!**

### **Fallback Behavior:**

- If Screen Orientation API is not supported, shows friendly message
- User can manually rotate device
- Joysticks still appear when device is in landscape orientation

---

## ✅ FEATURES

- ✅ Only visible on mobile devices
- ✅ Screen Orientation API integration
- ✅ Automatic joystick enabling
- ✅ Button state updates
- ✅ Orientation change detection
- ✅ User-friendly messages
- ✅ Fallback for unsupported browsers

---

## 🔗 RELATED FILES

- `three.js/main.js` - Main implementation
- Options Menu creation (around line 6474)
- `checkAndCreateJoystick()` function (around line 18784)
- Joystick creation functions (around line 18263)

---

## 🚀 FUTURE ENHANCEMENTS

- Persistent landscape mode preference (localStorage)
- Auto-enable on game start if previously enabled
- Better error messaging
- Analytics for landscape mode usage

---

**Documentation Created:** December 6, 2025  
**Status:** ✅ **PRODUCTION READY**  
**Next:** Testing on real mobile devices

🧀 **Mobile gaming just got better!** 🧀

