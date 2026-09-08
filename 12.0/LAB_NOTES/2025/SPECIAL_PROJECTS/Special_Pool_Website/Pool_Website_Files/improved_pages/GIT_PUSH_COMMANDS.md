# 🚀 Git Push Commands - Step by Step

## 📋 Step-by-Step Commands

Run these commands in PowerShell (one at a time):

### **Step 1: Navigate to Project Directory**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world
```

### **Step 2: Check Status**
```powershell
git status
```

### **Step 3: Add Pool Website Files**
```powershell
# Add all pool website email integration files
git add "12.0/LAB_NOTES/2025/SPECIAL_PROJECTS/Special_Pool_Website/Pool_Website_Files/improved_pages/api/email-api-service.php"
git add "12.0/LAB_NOTES/2025/SPECIAL_PROJECTS/Special_Pool_Website/Pool_Website_Files/improved_pages/api/send-email.php"
git add "12.0/LAB_NOTES/2025/SPECIAL_PROJECTS/Special_Pool_Website/Pool_Website_Files/improved_pages/admin/settings.json"
```

### **Step 4: Add Documentation Files (Optional)**
```powershell
git add "12.0/LAB_NOTES/2025/SPECIAL_PROJECTS/Special_Pool_Website/Pool_Website_Files/improved_pages/*.md"
```

### **Step 5: Commit Changes**
```powershell
git commit -m "feat(pool-website): Add Resend API email integration - No SMTP required

- Add Resend API service for contact forms
- All 3 forms (kontakt, anfragen, index) now use Resend API
- Free tier: 3,000 emails/month
- Automatic fallback: Resend -> SMTP -> PHP mail()
- Emails delivered to office@poolbauprofi.at"
```

### **Step 6: Push to Repository**
```powershell
git push
```

---

## 🎯 Quick One-Liner (All Steps)

If you want to do it all at once:

```powershell
cd C:\xampp-server\htdocs\narrrfs-world; git add "12.0/LAB_NOTES/2025/SPECIAL_PROJECTS/Special_Pool_Website/"; git commit -m "feat(pool-website): Add Resend API email integration"; git push
```

---

## ✅ Verify Before Push

### **Check What Will Be Committed:**
```powershell
git status
```

### **See What Changed:**
```powershell
git diff --cached
```

---

## 🔍 After Push

1. **Check Git Status:**
   ```powershell
   git status
   ```
   Should show: "nothing to commit, working tree clean"

2. **Verify Remote:**
   ```powershell
   git remote -v
   ```

3. **Check Last Commit:**
   ```powershell
   git log -1
   ```

---

## 🚨 If You Get Errors

### **If "nothing to commit":**
- Files might already be committed
- Check with `git status`

### **If "remote rejected":**
- Someone else pushed changes
- Run: `git pull` first, then `git push`

### **If authentication error:**
- Check your git credentials
- May need to set up SSH key or use HTTPS with token

---

## 📝 Alternative: Add All Pool Website Files

If you want to add everything in the pool website folder:

```powershell
git add "12.0/LAB_NOTES/2025/SPECIAL_PROJECTS/Special_Pool_Website/"
git commit -m "feat(pool-website): Add Resend API email integration"
git push
```

---

**Ready to push!** 🚀

