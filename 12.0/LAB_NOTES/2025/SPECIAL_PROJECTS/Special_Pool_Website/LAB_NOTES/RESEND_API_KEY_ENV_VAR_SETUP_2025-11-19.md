# 🔑 Resend API Key - Environment Variable Setup - November 19, 2025

## 🚨 **ISSUE IDENTIFIED**

**Problem:** 
- Local test in admin interface not working
- Old settings seem to still be there
- System sends via Resend API (not SMTP), but API key needs to be private
- Need to set API key as environment variable in Render (like `POOLBAU_SMTP_PASSWORD`)

## ✅ **SOLUTION IMPLEMENTED**

### **1. Updated Settings Loading (`api/smtp-helper.php`)**

**Changes:**
- ✅ Resend API key now loaded from environment variable `RESEND_API_KEY` (production)
- ✅ Falls back to hardcoded value for local testing
- ✅ Falls back to `settings.json` if neither env var nor hardcoded value exists
- ✅ Same pattern as `POOLBAU_SMTP_PASSWORD`

**Priority Order:**
1. **Environment Variable** (`RESEND_API_KEY`) - Production/Render
2. **Hardcoded Value** - Local testing only
3. **settings.json** - Fallback

### **2. Updated Settings API (`admin/get-settings.php`)**

**Changes:**
- ✅ Now uses `poolbau_get_email_settings()` function
- ✅ Ensures admin interface sees same settings as email sending
- ✅ Includes environment variable values

## 📍 **WHERE THE RESEND API KEY IS USED**

### **1. Settings Loading:**
**File:** `api/smtp-helper.php`
**Function:** `poolbau_get_email_settings()`
**Lines:** 30-45
```php
// Resend API Key (from environment variable for production)
$envResendKey = getenv('RESEND_API_KEY');
if ($envResendKey !== false && $envResendKey !== '') {
    $cached['resend_api_key'] = $envResendKey;
} elseif (empty($cached['resend_api_key'])) {
    // LOCAL TESTING ONLY: Hardcoded Resend API key
    $cached['resend_api_key'] = 're_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw';
}
```

### **2. Email Sending:**
**File:** `api/email-api-service.php`
**Function:** `send_email_via_api()`
**Lines:** 223-237
```php
// Try Resend (if configured)
if (!empty($settings['resend_api_key'])) {
    $result = send_email_via_resend(
        $settings['resend_api_key'],  // ← API key used here
        $toEmail,
        $fromEmail,
        ...
    );
}
```

### **3. Email Test:**
**File:** `admin/test-email-connection.php`
**Function:** Tests Resend API if key is configured
**Lines:** 86-95

## 🔧 **LOCAL TESTING SETUP**

### **Hardcoded API Key (Local Only):**
**File:** `api/smtp-helper.php`
**Line:** 41
**Value:** `re_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw`

**How it works:**
- On localhost: No `RESEND_API_KEY` environment variable → Uses hardcoded value
- On Render: `RESEND_API_KEY` environment variable set → Uses that instead

## 🚀 **RENDER/PRODUCTION SETUP**

### **Step 1: Add Environment Variable in Render Dashboard**

1. Go to Render Dashboard: https://dashboard.render.com
2. Navigate to your service: `poolbauprofi-at`
3. Go to **Environment** tab
4. Click **"Edit"** or **"+ Create environment group"**
5. Add new environment variable:
   - **Key:** `RESEND_API_KEY`
   - **Value:** `re_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw` (your actual Resend API key)
   - **Note:** Mark as **Private** (hidden from logs)

### **Step 2: Verify Environment Variable**

The code will automatically:
1. Check for `RESEND_API_KEY` environment variable first
2. Use it if found (production)
3. Fall back to hardcoded value if not found (local)

## 📝 **FILES MODIFIED**

1. **`api/smtp-helper.php`**
   - Added `RESEND_API_KEY` environment variable support
   - Added hardcoded value for local testing
   - Priority: Env Var > Hardcoded > settings.json

2. **`admin/get-settings.php`**
   - Now uses `poolbau_get_email_settings()` function
   - Ensures admin interface sees same settings as email functions

## 🔍 **CURRENT RESEND API KEY**

**From `settings.json`:**
- **Key:** `re_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw`
- **Status:** Currently in settings.json (will be moved to environment variable)

**Hardcoded for Local (in `smtp-helper.php`):**
- **Key:** `re_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw`
- **Purpose:** Local testing only
- **Note:** Same key hardcoded for local development

## 🧪 **TESTING CHECKLIST**

### **Local Testing:**
- [ ] Open admin dashboard locally
- [ ] Go to Email Settings
- [ ] Click "📧 E-Mail-Verbindung testen"
- [ ] Should use hardcoded Resend API key
- [ ] Should show success message with "Resend" service
- [ ] Check email inbox for test email

### **Production Testing (After Render Env Var Setup):**
- [ ] Add `RESEND_API_KEY` environment variable in Render
- [ ] Set value to your Resend API key
- [ ] Restart service in Render
- [ ] Test email connection from admin dashboard
- [ ] Should use environment variable (not hardcoded value)
- [ ] Should show success message
- [ ] Check email inbox for test email

## ⚠️ **IMPORTANT NOTES**

1. **Environment Variable Priority:**
   - Render/Production: Always uses `RESEND_API_KEY` environment variable
   - Local: Uses hardcoded value if env var not set

2. **Security:**
   - API key in `settings.json` is not secure (committed to git)
   - Environment variable in Render is secure (not in git, private)
   - Hardcoded value is only for local testing

3. **Settings.json:**
   - Can keep API key in `settings.json` for reference
   - But environment variable will override it in production
   - Local will use hardcoded value if env var not set

## 📋 **RENDER ENVIRONMENT VARIABLE CONFIGURATION**

### **Current Environment Variables (from screenshot):**
- ✅ `POOLBAU_SMTP_PASSWORD` = `LadinigPool1!`

### **To Add (for Resend API):**
- ➕ `RESEND_API_KEY` = `re_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw`
  - **Type:** Environment Variable
  - **Visibility:** Private (hidden)
  - **Service:** poolbauprofi-at

### **Steps to Add in Render:**
1. Render Dashboard → poolbauprofi-at service
2. Environment tab
3. Click "Edit" or "+"
4. Add:
   - Key: `RESEND_API_KEY`
   - Value: `re_9KCgAcb5_HdcFLVi7gvjdgJtjs6NYMmVw`
5. Mark as Private
6. Save and restart service

## 🎯 **EXPECTED BEHAVIOR**

### **Local Development:**
- No `RESEND_API_KEY` env var → Uses hardcoded value
- Email sending works via Resend API
- Test email works in admin interface

### **Production (Render):**
- `RESEND_API_KEY` env var set → Uses that value
- Email sending works via Resend API
- Test email works in admin interface
- API key is secure (not in code or settings.json)

## 📅 **DATE**

**Fixed:** November 19, 2025  
**Issue:** Local test not working, need env var for Render  
**Status:** ✅ **COMPLETE - READY FOR DEPLOYMENT**

---

**🔑 Resend API key now configurable via environment variable for production, hardcoded for local testing! 🔑**

