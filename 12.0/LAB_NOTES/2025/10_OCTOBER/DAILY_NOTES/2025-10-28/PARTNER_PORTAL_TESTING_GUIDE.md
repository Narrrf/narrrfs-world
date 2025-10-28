# 🧪 PARTNER PORTAL SYSTEM - TESTING GUIDE

**Date:** October 28, 2025  
**Status:** ✅ Ready for Testing  
**Purpose:** Step-by-step guide to test the complete Partner Portal System  

---

## 🎯 **TESTING OBJECTIVES**

1. ✅ Verify database and demo data
2. ✅ Test admin interface functionality
3. ✅ Test frontend partner showcase
4. ✅ Verify navigation integration
5. ✅ Test image upload system
6. ✅ Prepare for production deployment

---

## 🗄️ **STEP 1: VERIFY DATABASE**

### **Check Table Creation:**
```powershell
cd C:\xampp-server\htdocs\narrrfs-world
sqlite3 db/narrrf_world.sqlite ".schema tbl_partners"
```

**Expected Output:**
```sql
CREATE TABLE tbl_partners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  partner_name TEXT NOT NULL,
  ...
);
CREATE INDEX idx_partners_active ON tbl_partners(is_active);
...
```

### **Verify Demo Data:**
```powershell
sqlite3 db/narrrf_world.sqlite "SELECT partner_name, partner_type, is_featured FROM tbl_partners ORDER BY display_order;"
```

**Expected Output:**
```
Gensuki|NFT Project|1
Golden Baboons|Gaming Community|1
[Partner Name]|Community Partner|0
```

---

## 🎨 **STEP 2: TEST ADMIN INTERFACE**

### **Open Admin Interface:**
```
http://localhost/public/admin-interface.html
```

### **Navigate to Partners Tab:**
1. **Login** if required (admin credentials)
2. **Click** "🤝 Partners" tab button
3. **Verify** form loads with all fields
4. **Verify** partners list loads below form

### **Test Add New Partner:**

**Fill out form:**
- **Name:** "Test Community"
- **Slug:** (leave empty - should auto-generate)
- **Type:** "Community Partner"
- **Short Desc:** "This is a test partner for verification"
- **Long Desc:** "Full description for testing the modal popup display"
- **Discord:** "https://discord.gg/test"
- **Twitter:** "https://twitter.com/test"
- **Website:** "https://test.com"
- **Featured:** ✅ Check
- **Active:** ✅ Check

**Click** "➕ Add Partner"

**Expected:**
- ✅ Alert: "Partner added successfully!"
- ✅ Form clears
- ✅ Partners list refreshes
- ✅ New partner appears in list

### **Test Edit Partner:**

1. **Click** "✏️ Edit" on "Test Community"
2. **Verify** form populates with data
3. **Change** short description to "Updated test description"
4. **Click** "💾 Update Partner"

**Expected:**
- ✅ Alert: "Partner updated successfully!"
- ✅ Form returns to add mode
- ✅ Partners list shows updated description

### **Test Image Upload:**

**Logo Upload:**
1. **Click** "🖼️ Logo" on "Test Community"
2. **Select** a small test image (< 5MB, JPG/PNG)
3. **Wait** for upload

**Expected:**
- ✅ Alert: "Logo uploaded successfully!"
- ✅ Partners list refreshes
- ✅ Logo thumbnail appears in list

**Banner Upload:**
1. **Click** "🎨 Banner" on "Test Community"
2. **Select** a test banner image
3. **Wait** for upload

**Expected:**
- ✅ Alert: "Banner uploaded successfully!"
- ✅ Image saved to img/partners/

### **Test Toggle Active:**

1. **Click** "⏸️" button on "Test Community"

**Expected:**
- ✅ Partner list refreshes
- ✅ Status badge changes to "⏸️ Inactive"
- ✅ Button changes to "▶️"

2. **Click** "▶️" to reactivate

**Expected:**
- ✅ Status badge changes to "✅ Active"

### **Test Delete:**

1. **Click** "🗑️ Delete" on "Test Community"
2. **Confirm** deletion in popup

**Expected:**
- ✅ Confirmation dialog appears
- ✅ After confirm, alert: "Partner deleted successfully!"
- ✅ Partners list refreshes
- ✅ "Test Community" removed from list

---

## 🌐 **STEP 3: TEST FRONTEND PAGE**

### **Open Partners Page:**
```
http://localhost/public/partners.html
```

### **Verify Page Load:**

**Check Featured Section:**
- ✅ Heading: "⭐ Featured Partners"
- ✅ Cards for Gensuki and Golden Baboons
- ✅ Featured badge visible on cards
- ✅ Logo displays (or fallback cheese-egg.png)

**Check All Partners Section:**
- ✅ Heading: "🌐 All Partners"
- ✅ Card for [Partner Name] placeholder
- ✅ Type badge displays correctly
- ✅ "Learn More" button visible

### **Test Partner Card:**

1. **Click** on "Gensuki" card

**Expected:**
- ✅ Modal slides in smoothly
- ✅ Overlay darkens background
- ✅ Partner details display
- ✅ Social links visible
- ✅ Close button (×) in top-right

2. **Click** close button or overlay

**Expected:**
- ✅ Modal closes smoothly
- ✅ Returns to partners page

### **Test Social Links:**

1. **Open** modal for any partner
2. **Click** Discord button (if URL valid)

**Expected:**
- ✅ Opens in new tab
- ✅ Goes to correct Discord invite

3. **Test** Twitter and Website buttons

### **Test Keyboard Controls:**

1. **Open** any partner modal
2. **Press** ESC key

**Expected:**
- ✅ Modal closes

### **Test Mobile Responsive:**

1. **Resize** browser to mobile width (< 768px)

**Expected:**
- ✅ Card grid changes to 1 column
- ✅ Modal fits mobile screen
- ✅ Social buttons stack properly
- ✅ All text readable

---

## 🔗 **STEP 4: TEST NAVIGATION INTEGRATION**

### **Check All Pages:**

**Test Each Page:**
1. `http://localhost/public/index.html`
2. `http://localhost/public/mint.html`
3. `http://localhost/public/get-roles.html`
4. `http://localhost/public/whitepaper-pro.html`
5. `http://localhost/public/faq.html`
6. `http://localhost/public/project-updates.html`

**For Each Page:**
- ✅ Verify "Partners" link in navigation
- ✅ Click "Partners" link
- ✅ Verify goes to partners.html
- ✅ Click browser back button
- ✅ Return to original page

---

## 🖼️ **STEP 5: TEST IMAGE UPLOAD SYSTEM**

### **Prepare Test Images:**
- Logo: 200x200px or similar (square recommended)
- Banner: 800x400px or similar (wide format)
- File types: JPG or PNG
- File size: < 5MB each

### **Upload Test:**

**Via Admin Interface:**
1. Open admin-interface.html → Partners tab
2. Click "🖼️ Logo" on any partner
3. Select test logo image
4. Wait for success message
5. Refresh partners.html
6. Verify logo appears on card

**Check File System:**
```powershell
Get-ChildItem "C:\xampp-server\htdocs\narrrfs-world\public\img\partners"
```

**Expected:**
- ✅ Uploaded file visible
- ✅ Filename format: `{id}_logo_{timestamp}.{ext}`

---

## 🧪 **STEP 6: API TESTING**

### **Test Public API:**

**PowerShell:**
```powershell
curl http://localhost/api/user/get-partners.php
```

**Expected JSON Response:**
```json
{
  "success": true,
  "partners": {
    "featured": [...],
    "all": [...],
    "regular": [...]
  },
  "total": 3,
  "featured_count": 2
}
```

### **Test Admin API:**

**Get All Partners:**
```powershell
# Must be logged in as admin first!
curl "http://localhost/api/admin/partner-management.php?action=get_all"
```

**Expected:**
```json
{
  "success": true,
  "partners": [...],
  "total": 3
}
```

---

## ✅ **TESTING CHECKLIST**

### **Database:**
- [ ] Table exists
- [ ] Indexes created
- [ ] Demo data inserted (3 partners)
- [ ] Schema correct

### **Admin Interface:**
- [ ] Partners tab opens
- [ ] Form loads correctly
- [ ] Partners list displays
- [ ] Can add new partner
- [ ] Can edit partner
- [ ] Can upload logo
- [ ] Can upload banner
- [ ] Can toggle active
- [ ] Can delete partner

### **Frontend:**
- [ ] partners.html loads
- [ ] Featured section displays
- [ ] All partners grid displays
- [ ] Partner cards clickable
- [ ] Modal opens smoothly
- [ ] Modal closes (×, overlay, ESC)
- [ ] Social links work
- [ ] Mobile responsive

### **Navigation:**
- [ ] index.html has Partners link
- [ ] mint.html has Partners link
- [ ] get-roles.html has Partners link
- [ ] whitepaper-pro.html has Partners link
- [ ] faq.html has Partners link (2 places)
- [ ] project-updates.html has Partners link (2 places)
- [ ] All links work correctly

### **APIs:**
- [ ] get-partners.php returns data
- [ ] partner-management.php get_all works
- [ ] partner-management.php add works
- [ ] partner-management.php update works
- [ ] partner-management.php delete works
- [ ] partner-management.php upload works

---

## 🚀 **AFTER TESTING PASSES**

### **Next Steps:**

1. **Customize Partners:**
   - Add real partner data through admin
   - Upload actual partner logos
   - Add real social links
   - Set correct featured status

2. **Test Production:**
   - Deploy to Render
   - Create table on production DB
   - Create img/partners directory
   - Test all features on live site

3. **Promote:**
   - Announce new Partners page
   - Share with partner communities
   - Encourage cross-promotion

---

## ⚠️ **TROUBLESHOOTING**

### **If Partners Don't Load:**
- Check browser console for errors
- Verify API endpoint: `/api/user/get-partners.php`
- Check database has demo data
- Verify file permissions

### **If Upload Fails:**
- Check img/partners directory exists
- Verify directory permissions (755)
- Check file size (< 5MB)
- Check file type (JPG, PNG, GIF, WEBP)

### **If Admin Can't Access:**
- Verify logged in as admin
- Check session authentication
- Verify admin role in database

---

**🧪 TESTING GUIDE COMPLETE - READY FOR VERIFICATION! ✅**

---

**Created:** October 28, 2025 - 23:40  
**Purpose:** Comprehensive testing before production  
**Status:** Ready for user testing

