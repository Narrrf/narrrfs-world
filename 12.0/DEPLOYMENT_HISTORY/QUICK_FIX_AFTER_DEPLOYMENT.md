# ⚡ QUICK FIX - AFTER EVERY DEPLOYMENT

**Time Required:** 5 seconds  
**Run On:** Render Shell  
**When:** After every push to `render-deploy`  

---

## 🚨 COPY & PASTE THESE COMMANDS:

```bash
ln -s /data/img/partners /var/www/html/img/partners && chown -h www-data:www-data /var/www/html/img/partners && echo "✅ Partner images restored!"
```

**That's it!** One line, 5 seconds, all partner images appear.

---

## ✅ VERIFICATION

**After running, test:**
```bash
ls -la /var/www/html/img/partners  # Should show: -> /data/img/partners
```

**Then check:**
- Visit `https://narrrfs.world/partners.html`
- All logos should appear (no cheese placeholders)
- Gallery images should display correctly

---

## 🔮 FUTURE: FULL AUTOMATION

**Next step:** Add to Render starter script so this runs automatically on every deployment.

**See:** `PARTNER_IMAGE_PERSISTENCE_FINAL_SOLUTION.md` for complete details.

---

**🧀 BOOKMARK THIS - USE AFTER EVERY PUSH! 🧀**

