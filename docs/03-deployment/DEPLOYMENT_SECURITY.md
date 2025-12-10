# 🔒 Security & Deployment Guide for Laravel Forge

## ⚠️ Security Checklist - MUST DO Before Deployment

### 🔴 Critical Security Issues to Fix

#### 1. **Remove Test/Demo Data**
```bash
# DO NOT deploy with seeded test data!
# Remove or modify DatabaseSeeder.php
```

**Why?** Test accounts with known passwords (`password`) are a security risk.

**Fix:**
- Remove all test users from seeder
- Or use strong, random passwords
- Or disable seeding in production

#### 2. **Environment Configuration**
```bash
# .env file - NEVER commit this!
APP_ENV=production
APP_DEBUG=false  # MUST be false in production
APP_KEY=base64:... # Generate new key

DB_PASSWORD=strong_random_password_here
```

**Critical Settings:**
- `APP_DEBUG=false` - Prevents error details from showing
- `APP_ENV=production` - Enables production optimizations
- Strong database password
- Unique APP_KEY

#### 3. **Remove Development Routes**
Check `routes/web.php` for any debug/test routes and remove them.

#### 4. **CSRF Protection**
✅ Already enabled (Laravel default)
- All forms use `@csrf`
- API routes should use Sanctum tokens

#### 5. **SQL Injection Protection**
✅ Using Eloquent ORM (protected by default)
- Never use raw queries with user input
- Always use parameter binding

#### 6. **XSS Protection**
✅ Blade templates escape output by default
- Use `{{ }}` not `{!! !!}` for user input
- Sanitize any HTML input

---

## 🛡️ Security Improvements Needed

### High Priority

#### 1. **Password Reset Functionality**
Currently missing! Add:
```bash
php artisan make:controller Auth/PasswordResetController
```

#### 2. **Rate Limiting**
Add to `app/Http/Kernel.php`:
```php
'api' => [
    'throttle:60,1', // 60 requests per minute
],
```

#### 3. **Email Verification**
Enable in `User` model:
```php
class User extends Authenticatable implements MustVerifyEmail
```

#### 4. **Two-Factor Authentication**
Consider adding Laravel Fortify for 2FA.

#### 5. **File Upload Validation**
In `AttachmentController.php`, ensure:
```php
$request->validate([
    'file' => 'required|file|mimes:jpg,png,pdf|max:2048',
]);
```

#### 6. **Input Validation**
✅ Most controllers have validation
⚠️ Review all controllers for complete validation

---

## 🚀 Laravel Forge Deployment Steps

### Step 1: Prepare Your Repository

1. **Update .gitignore**
```bash
# Ensure these are ignored
.env
.env.backup
.phpunit.result.cache
node_modules/
public/hot
public/storage
storage/*.key
vendor/
```

2. **Commit all changes**
```bash
git add .
git commit -m "Prepare for production deployment"
git push origin main
```

### Step 2: Server Setup on Forge

1. **Create New Server**
   - Choose provider (DigitalOcean, AWS, etc.)
   - Select PHP 8.2+
   - Choose server size (minimum 1GB RAM)
   - Select region closest to users

2. **Server Configuration**
   - Enable firewall
   - Install MySQL/PostgreSQL
   - Install Redis (optional, for caching)
   - Enable automatic security updates

### Step 3: Create Site on Forge

1. **New Site**
   - Domain: `yourdomain.com`
   - Project type: Laravel
   - Web directory: `/public`

2. **Repository**
   - Connect GitHub
   - Select: `arunenoah/XpenseSettle`
   - Branch: `main`
   - Enable Quick Deploy

### Step 4: Environment Variables

In Forge, set these environment variables:

```env
APP_NAME="ExpenseSettle"
APP_ENV=production
APP_KEY=base64:... # Generate new!
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expensesettle_prod
DB_USERNAME=forge
DB_PASSWORD=STRONG_RANDOM_PASSWORD

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 5: Deploy Script

Forge default deploy script (customize if needed):

```bash
cd /home/forge/yourdomain.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci
npm run build
php artisan storage:link
php artisan optimize
```

### Step 6: SSL Certificate

1. In Forge, go to SSL
2. Click "LetsEncrypt"
3. Enable "Force HTTPS"

### Step 7: Database Setup

```bash
# SSH into server
forge ssh

# Create database
mysql -u forge -p
CREATE DATABASE expensesettle_prod;
exit

# Run migrations
cd /home/forge/yourdomain.com
php artisan migrate --force
```

### Step 8: Scheduler (if using)

In Forge, add Scheduler:
```bash
php artisan schedule:run
```

---

## 🔐 Post-Deployment Security

### 1. **Change Default Passwords**
- Remove all test accounts
- Create admin account with strong password

### 2. **Enable Monitoring**
- Set up Laravel Telescope (dev only!)
- Use Laravel Horizon for queues
- Enable error tracking (Sentry, Bugsnag)

### 3. **Backup Strategy**
In Forge:
- Enable daily backups
- Store in S3 or similar
- Test restore process

### 4. **Security Headers**
Add to `public/.htaccess`:
```apache
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### 5. **Regular Updates**
```bash
# Keep dependencies updated
composer update
npm update

# Check for security vulnerabilities
composer audit
npm audit
```

---

## ⚠️ Known Security Concerns

### Current Issues:

1. **❌ Test Data with Known Passwords**
   - **Risk**: High
   - **Fix**: Remove seeder or use strong passwords

2. **❌ No Email Verification**
   - **Risk**: Medium
   - **Fix**: Implement email verification

3. **❌ No Rate Limiting on Login**
   - **Risk**: Medium
   - **Fix**: Add throttle middleware

4. **❌ No 2FA**
   - **Risk**: Medium
   - **Fix**: Add Laravel Fortify

5. **⚠️ File Upload Without Virus Scan**
   - **Risk**: Medium
   - **Fix**: Add ClamAV or similar

6. **⚠️ No Password Complexity Rules**
   - **Risk**: Low
   - **Fix**: Add validation rules

---

## ✅ Security Features Already Implemented

- ✅ CSRF Protection
- ✅ SQL Injection Protection (Eloquent)
- ✅ XSS Protection (Blade escaping)
- ✅ Password Hashing (bcrypt)
- ✅ Authentication middleware
- ✅ Authorization checks (isAdmin, hasMember)
- ✅ Input validation on forms
- ✅ Secure session handling

---

## 🎯 Pre-Deployment Checklist

### Before you deploy, complete these:

- [ ] Set `APP_DEBUG=false`
- [ ] Generate new `APP_KEY`
- [ ] Remove or secure test accounts
- [ ] Set strong database password
- [ ] Review all validation rules
- [ ] Test file upload limits
- [ ] Enable HTTPS/SSL
- [ ] Set up backups
- [ ] Configure error logging
- [ ] Test on staging first
- [ ] Review all routes for security
- [ ] Check for exposed sensitive data
- [ ] Set up monitoring
- [ ] Document admin credentials securely

---

## 🚨 Emergency Response

If compromised:

1. **Immediately**:
   - Change all passwords
   - Rotate APP_KEY
   - Check logs for suspicious activity
   - Block suspicious IPs

2. **Investigation**:
   - Review access logs
   - Check database for unauthorized changes
   - Scan for malware

3. **Recovery**:
   - Restore from backup if needed
   - Patch vulnerability
   - Notify users if data exposed

---

## 📞 Support & Resources

- **Laravel Security**: https://laravel.com/docs/security
- **Forge Docs**: https://forge.laravel.com/docs
- **OWASP Top 10**: https://owasp.org/www-project-top-ten/
- **Laravel Security Checklist**: https://github.com/Checkmarx/laravel-security-checklist

---

## 💡 Recommendations

### For Production:

1. **Start with staging environment**
2. **Use strong passwords everywhere**
3. **Enable all security features**
4. **Monitor logs regularly**
5. **Keep everything updated**
6. **Have a backup strategy**
7. **Test disaster recovery**

### Consider Adding:

- Laravel Sanctum (API tokens)
- Laravel Fortify (2FA)
- Laravel Telescope (debugging - dev only!)
- Sentry (error tracking)
- CloudFlare (DDoS protection)
- Regular security audits

---

## ✅ Is It Safe to Deploy?

**Current Status**: ⚠️ **NOT PRODUCTION READY**

**Why?**
- Test accounts with known passwords
- Missing critical security features
- No email verification
- No rate limiting

**To Make Production Ready:**
1. Remove/secure all test data
2. Add rate limiting
3. Implement email verification
4. Add 2FA (recommended)
5. Set up proper monitoring
6. Test thoroughly on staging

**Timeline**: 1-2 days of security hardening needed

---

<div align="center">

**🔒 Security First, Deploy Second**

Take time to secure your app properly. Your users' data depends on it!

</div>
