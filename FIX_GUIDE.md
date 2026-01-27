# 🚀 Akanyenyeri Magazine Routing Fix Guide

## 🔧 Problem: "Not Found" Errors

You're seeing "Not Found" errors when clicking links because Apache isn't processing the `.htaccess` file properly. This is a common issue with XAMPP installations.

## 🎯 Solutions (Try in this order):

---

### **🔹 SOLUTION 1: Quick Fix - Use Direct URLs (Easiest)**

Instead of using clean URLs like:
- `/about` → Use: `/public/about.php`
- `/services` → Use: `/public/services.php`
- `/privacy` → Use: `/public/privacy.php`

**Post URLs:**
Instead of clicking post titles, use:
- `/public/single.php?slug=post-slug-here`

---

### **🔹 SOLUTION 2: Enable mod_rewrite in Apache (Recommended)**

1. **Open Apache configuration file:**
   - Location: `C:\xampp\apache\conf\httpd.conf`
   - Open with Notepad or VS Code as Administrator

2. **Enable mod_rewrite:**
   - Find this line: `#LoadModule rewrite_module modules/mod_rewrite.so`
   - Remove the `#` at the beginning: `LoadModule rewrite_module modules/mod_rewrite.so`

3. **Allow .htaccess overrides:**
   - Find the section that looks like:
     ```apache
     <Directory "C:/xampp/htdocs">
         ...
         AllowOverride None
         ...
     </Directory>
     ```
   - Change `AllowOverride None` to `AllowOverride All`

4. **Save the file and restart Apache:**
   - Save changes to `httpd.conf`
   - Open XAMPP Control Panel
   - Click "Stop" then "Start" for Apache

---

### **🔹 SOLUTION 3: Use the Simple Router (No .htaccess needed)**

1. **Rename `router.php` to `index.php`:**
   - Delete or rename your current `index.php`
   - Rename `router.php` to `index.php`

2. **This router will handle all URLs without needing .htaccess**

---

### **🔹 SOLUTION 4: Test and Verify**

1. **Run the routing test:**
   - Open: `http://localhost/akanyenyeri/test-routing-fallback.php`
   - This will show you exactly what's working and what's not

2. **Add sample posts:**
   - Open: `http://localhost/akanyenyeri/add_sample_posts.php`
   - This will create test posts if none exist

---

## 📋 Step-by-Step Troubleshooting:

### **1. Check if files exist:**
- ✅ `public/about.php` - EXISTS
- ✅ `public/services.php` - EXISTS (just created)
- ✅ `public/privacy.php` - EXISTS
- ✅ `public/terms.php` - EXISTS
- ✅ `public/cookies.php` - EXISTS
- ✅ `public/single.php` - EXISTS

### **2. Test direct access:**
Try these URLs in your browser:

- **Content Pages:**
  - [About Page](http://localhost/akanyenyeri/public/about.php)
  - [Services Page](http://localhost/akanyenyeri/public/services.php)
  - [Privacy Policy](http://localhost/akanyenyeri/public/privacy.php)
  - [Terms of Service](http://localhost/akanyenyeri/public/terms.php)
  - [Cookie Policy](http://localhost/akanyenyeri/public/cookies.php)

- **Post Pages (after adding samples):**
  - [Post 1](http://localhost/akanyenyeri/public/single.php?slug=breaking-news-major-political-development)
  - [Post 2](http://localhost/akanyenyeri/public/single.php?slug=tech-innovation-new-ai-breakthrough)

### **3. Common Issues and Fixes:**

| Issue | Solution |
|-------|----------|
| "Not Found" on all pages | Enable mod_rewrite in Apache |
| Clean URLs don't work | Use direct URLs with `.php` extension |
| Post pages show 404 | Add sample posts first, then use direct URL |
| Services page missing | Now created at `public/services.php` |
| Database connection failed | Check XAMPP MySQL is running |

---

## 🎉 Final Verification:

After applying the fixes:

1. **Test clean URLs:**
   - `/about`, `/services`, `/privacy`, etc.

2. **Test post URLs:**
   - Click on post titles from homepage
   - Or use: `/post-slug-here`

3. **Test admin access:**
   - `/admin/` should work

---

## 📚 Additional Help:

- **XAMPP Apache Configuration Guide:** [https://www.apachefriends.org/docs/](https://www.apachefriends.org/docs/)
- **mod_rewrite Documentation:** [https://httpd.apache.org/docs/current/mod/mod_rewrite.html](https://httpd.apache.org/docs/current/mod/mod_rewrite.html)

If you still have issues, the `test-routing-fallback.php` script will give you detailed diagnostics about what's working and what needs to be fixed.