# 📧 Email Configuration Guide - Pool Website

## 🚨 Critical Issue Fixed

**Problem:** Forms were trying to use customer's email credentials (`office@poolbauprofi.at`) as the sender, which requires their SMTP password.

**Solution:** Use YOUR email server credentials to send emails TO the customer's address.

---

## ✅ Correct Email Configuration

### **How It Works Now:**

1. **SMTP Credentials** → YOUR email server (e.g., `smtp.yourdomain.com`)
2. **FROM Address** → YOUR email address (e.g., `noreply@yourdomain.com`)
3. **TO Address** → Customer's office email (`office@poolbauprofi.at`)
4. **Reply-To** → Customer's email (the person who filled the form)

### **Email Flow:**
```
Form Submission → Your SMTP Server → office@poolbauprofi.at
```

---

## 🔧 Configuration Settings

### **Required Settings in `admin/settings.json`:**

```json
{
  "email_address": "office@poolbauprofi.at",  // WHERE emails are delivered (customer's office)
  "sender_email": "noreply@yourdomain.com",   // YOUR email (appears as sender)
  "from_name": "Poolbauprofi.at Website",     // Display name
  
  "smtp_host": "smtp.yourdomain.com",         // YOUR SMTP server
  "smtp_port": "587",                         // Usually 587 (TLS) or 465 (SSL)
  "smtp_username": "your-email@yourdomain.com", // YOUR SMTP username
  "smtp_password": "YOUR_SMTP_PASSWORD",       // YOUR SMTP password
  "smtp_encryption": "tls"                     // "tls" or "ssl"
}
```

### **Key Points:**

- ✅ **`email_address`** = Customer's office email (where emails go)
- ✅ **`sender_email`** = Your email address (appears as sender)
- ✅ **`smtp_username`** = Your SMTP credentials (for authentication)
- ✅ **`smtp_password`** = Your SMTP password (for authentication)

---

## 📝 Example Configurations

### **Example 1: Using Gmail SMTP**
```json
{
  "email_address": "office@poolbauprofi.at",
  "sender_email": "yourname@gmail.com",
  "from_name": "Poolbauprofi.at Website",
  "smtp_host": "smtp.gmail.com",
  "smtp_port": "587",
  "smtp_username": "yourname@gmail.com",
  "smtp_password": "your-app-password",
  "smtp_encryption": "tls"
}
```

### **Example 2: Using Your Own Domain SMTP**
```json
{
  "email_address": "office@poolbauprofi.at",
  "sender_email": "noreply@yourdomain.com",
  "from_name": "Poolbauprofi.at Website",
  "smtp_host": "smtp.yourdomain.com",
  "smtp_port": "587",
  "smtp_username": "noreply@yourdomain.com",
  "smtp_password": "your-smtp-password",
  "smtp_encryption": "tls"
}
```

### **Example 3: Using SendGrid or Similar Service**
```json
{
  "email_address": "office@poolbauprofi.at",
  "sender_email": "noreply@yourdomain.com",
  "from_name": "Poolbauprofi.at Website",
  "smtp_host": "smtp.sendgrid.net",
  "smtp_port": "587",
  "smtp_username": "apikey",
  "smtp_password": "your-sendgrid-api-key",
  "smtp_encryption": "tls"
}
```

---

## 🔒 Security Notes

### **Password Storage:**
- Passwords can be stored in `admin/settings.json` (not recommended for production)
- **Better:** Use environment variable `POOLBAU_SMTP_PASSWORD`
- Set environment variable before running PHP scripts

### **Environment Variable (Recommended):**
```bash
# Linux/Mac
export POOLBAU_SMTP_PASSWORD="your-password"

# Windows PowerShell
$env:POOLBAU_SMTP_PASSWORD="your-password"
```

---

## 🧪 Testing

### **Test Email Connection:**
1. Go to admin dashboard
2. Navigate to email settings
3. Use "Test Email Connection" feature
4. Check if email arrives at `office@poolbauprofi.at`

### **Test Form Submission:**
1. Fill out contact form on website
2. Submit form
3. Check `api/submitted_emails.txt` for backup copy
4. Verify email arrives at `office@poolbauprofi.at`

---

## ❌ Common Mistakes to Avoid

### **❌ WRONG Configuration:**
```json
{
  "smtp_username": "office@poolbauprofi.at",  // ❌ Customer's email
  "smtp_password": "customer-password",       // ❌ Customer's password
  "from_email": "office@poolbauprofi.at"      // ❌ Customer's email as sender
}
```

**Why this fails:**
- Requires customer's SMTP credentials
- Customer may not want to share password
- Authentication fails if credentials are wrong

### **✅ CORRECT Configuration:**
```json
{
  "smtp_username": "your-email@yourdomain.com",  // ✅ Your email
  "smtp_password": "your-password",               // ✅ Your password
  "sender_email": "noreply@yourdomain.com",       // ✅ Your email as sender
  "email_address": "office@poolbauprofi.at"       // ✅ Customer's email as recipient
}
```

---

## 📊 How It Works

### **Email Headers:**
```
From: Poolbauprofi.at Website <noreply@yourdomain.com>
To: office@poolbauprofi.at
Reply-To: Customer Name <customer@example.com>
Subject: Neue Anfrage von Poolbauprofi.at - Contact Form
```

### **SMTP Authentication:**
- Uses YOUR SMTP credentials to authenticate
- Sends email through YOUR email server
- Delivers email to customer's `office@poolbauprofi.at`

### **Result:**
- ✅ Customer receives email at their office address
- ✅ No need for customer's SMTP credentials
- ✅ Professional email delivery
- ✅ Replies go to the person who filled the form

---

## 🚀 Quick Setup Steps

1. **Get Your SMTP Credentials:**
   - SMTP host (e.g., `smtp.gmail.com`)
   - SMTP port (usually `587` for TLS)
   - SMTP username (your email)
   - SMTP password (your email password or app password)

2. **Update `admin/settings.json`:**
   - Set `sender_email` to your email
   - Set `smtp_host` to your SMTP server
   - Set `smtp_username` to your email
   - Set `smtp_password` to your password
   - Keep `email_address` as `office@poolbauprofi.at`

3. **Test Configuration:**
   - Use admin dashboard test feature
   - Submit a test form
   - Verify email arrives at customer's office

4. **Deploy:**
   - Configuration is ready
   - Forms will send emails correctly
   - No customer credentials needed

---

## 📞 Support

If you need help configuring your SMTP settings:
1. Check your email provider's SMTP documentation
2. Verify SMTP credentials are correct
3. Test connection using admin dashboard
4. Check `api/email_log.txt` for error messages

---

**Last Updated:** 2025-11-19  
**Status:** ✅ Fixed - Ready for Production  
**Version:** 2.0 - Email Relay Service

