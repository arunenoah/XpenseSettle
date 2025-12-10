# 🚀 GitHub to Laravel Forge Deployment Guide

## Complete Step-by-Step Guide

---

## 📋 Prerequisites

Before starting, ensure you have:
- ✅ GitHub repository: `https://github.com/arunenoah/XpenseSettle.git`
- ✅ Laravel Forge account (sign up at https://forge.laravel.com)
- ✅ Server provider account (DigitalOcean, AWS, Linode, etc.)
- ✅ Domain name (optional but recommended)
- ✅ All code committed and pushed to GitHub

---

## 🎯 Part 1: Prepare Your GitHub Repository

### Step 1: Verify Your Code is Pushed

```bash
cd /Users/arunkumar/Documents/Application/expenseSettle

# Check status
git status

# If there are uncommitted changes
git add .
git commit -m "Ready for deployment"
git push origin main

# Verify on GitHub
# Visit: https://github.com/arunenoah/XpenseSettle
```

### Step 2: Create GitHub Personal Access Token

1. Go to GitHub: https://github.com/settings/tokens
2. Click **"Generate new token"** → **"Generate new token (classic)"**
3. Name it: `Laravel Forge Deployment`
4. Select scopes:
   - ✅ `repo` (Full control of private repositories)
   - ✅ `admin:repo_hook` (Full control of repository hooks)
5. Click **"Generate token"**
6. **COPY THE TOKEN** - You won't see it again!
7. Save it somewhere safe temporarily

---

## 🖥️ Part 2: Set Up Laravel Forge

### Step 1: Create Forge Account

1. Go to https://forge.laravel.com
2. Sign up or log in
3. Choose a plan:
   - **Hobby**: $12/month (1 server)
   - **Growth**: $19/month (unlimited servers)
   - **Business**: $39/month (team features)

### Step 2: Connect Your Server Provider

1. In Forge, click **"Server Providers"** in sidebar
2. Click **"Connect Provider"**
3. Choose your provider:
   - **DigitalOcean** (Recommended for beginners)
   - **AWS**
   - **Linode**
   - **Vultr**
   - **Hetzner**

#### For DigitalOcean:
1. Go to https://cloud.digitalocean.com/account/api/tokens
2. Click **"Generate New Token"**
3. Name: `Laravel Forge`
4. Check **"Write"** scope
5. Copy the token
6. Paste in Forge

### Step 3: Create a New Server

1. In Forge, click **"Servers"** → **"Create Server"**
2. Fill in details:

```
Server Provider: DigitalOcean (or your choice)
Server Name: expensesettle-production
Server Size: 
  - Basic ($6/month) - For testing
  - Professional ($12/month) - Recommended
  - Business ($24/month) - For high traffic
Region: Choose closest to your users
  - Singapore (for Asia)
  - New York (for USA)
  - London (for Europe)
PHP Version: 8.2
Database: MySQL 8.0
```

3. Click **"Create Server"**
4. Wait 5-10 minutes for server provisioning

---

## 🌐 Part 3: Create Your Site on Forge

### Step 1: Add a New Site

1. Once server is ready, click on it
2. Click **"Sites"** → **"New Site"**
3. Fill in details:

```
Root Domain: yourdomain.com (or use server IP temporarily)
Project Type: General PHP / Laravel
Web Directory: /public
PHP Version: 8.2
Create Database: ✅ Yes
Database Name: expensesettle_prod
```

4. Click **"Add Site"**

### Step 2: Connect GitHub Repository

1. In your site, go to **"Git Repository"** section
2. Click **"Install Repository"**
3. Choose **"GitHub"**
4. Authorize Forge to access GitHub (if first time)
5. Fill in:

```
Repository: arunenoah/XpenseSettle
Branch: main
Install Composer Dependencies: ✅ Yes
```

6. Click **"Install Repository"**

---

## ⚙️ Part 4: Configure Environment Variables

### Step 1: Set Environment Variables

1. In your site, click **"Environment"** tab
2. You'll see the `.env` file
3. Update these critical values:

```env
APP_NAME="ExpenseSettle"
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expensesettle_prod
DB_USERNAME=forge
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 2: Generate APP_KEY

1. In Forge, go to your site
2. Click **"Commands"** tab
3. Run this command:

```bash
php artisan key:generate --show
```

4. Copy the output
5. Go back to **"Environment"** tab
6. Replace `APP_KEY=` with the generated key
7. Click **"Save"**

---

## 🔧 Part 5: Configure Deployment Script

### Step 1: Update Deploy Script

1. In your site, click **"Deployment"** tab
2. You'll see the deployment script
3. Replace with this optimized script:

```bash
cd /home/forge/yourdomain.com

# Enable maintenance mode
php artisan down || true

# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Clear and cache config
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run migrations (be careful in production!)
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Install npm dependencies and build assets
npm ci --production
npm run build

# Create storage link if not exists
php artisan storage:link || true

# Restart queue workers (if using queues)
# php artisan queue:restart

# Disable maintenance mode
php artisan up

# Clear OPcache (if enabled)
# php artisan optimize:clear

echo "🚀 Deployment completed successfully!"
```

4. Click **"Save"**

### Step 2: Enable Quick Deploy (Optional)

1. Toggle **"Quick Deploy"** ON
2. This will auto-deploy when you push to GitHub
3. Recommended for development, be careful in production

---

## 🔒 Part 6: Set Up SSL Certificate

### Step 1: Add SSL Certificate

1. In your site, click **"SSL"** tab
2. Choose **"LetsEncrypt"** (Free!)
3. Domains: `yourdomain.com, www.yourdomain.com`
4. Click **"Obtain Certificate"**
5. Wait 1-2 minutes

### Step 2: Force HTTPS

1. After SSL is active, toggle **"Force HTTPS"** ON
2. This redirects all HTTP traffic to HTTPS

---

## 🗄️ Part 7: Set Up Database

### Step 1: Run Migrations

1. Go to **"Commands"** tab
2. Run:

```bash
php artisan migrate --force
```

3. **DO NOT** run `php artisan migrate:fresh --seed` in production!

### Step 2: Create Admin User Manually

1. SSH into your server (click **"SSH"** button in Forge)
2. Navigate to your site:

```bash
cd /home/forge/yourdomain.com
```

3. Create admin user using tinker:

```bash
php artisan tinker
```

4. In tinker, run:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Your Name',
    'email' => 'your@email.com',
    'password' => Hash::make('YourStrong@Pass123'),
]);

exit
```

---

## 🚀 Part 8: Deploy Your Application

### Option 1: Manual Deploy

1. In Forge, go to your site
2. Click **"Deployment"** tab
3. Click **"Deploy Now"** button
4. Watch the deployment log
5. Wait for "Deployment completed successfully!"

### Option 2: Auto Deploy from GitHub

1. Push code to GitHub:

```bash
git add .
git commit -m "Update feature"
git push origin main
```

2. If Quick Deploy is enabled, Forge will automatically deploy
3. Check deployment log in Forge

---

## ✅ Part 9: Verify Deployment

### Step 1: Check Your Site

1. Visit: `https://yourdomain.com`
2. You should see the login page
3. Try logging in with your admin credentials

### Step 2: Test Features

- [ ] Login works
- [ ] Dashboard loads
- [ ] Can create a group
- [ ] Can add expenses
- [ ] Charts display correctly
- [ ] Mobile responsive works

### Step 3: Check Logs

1. In Forge, go to **"Logs"** tab
2. Check for any errors:
   - Application logs
   - PHP errors
   - Nginx errors

---

## 🔄 Part 10: Making Updates

### Workflow for Updates:

1. **Make changes locally**
```bash
# Edit your code
git add .
git commit -m "Add new feature"
git push origin main
```

2. **Automatic deployment** (if Quick Deploy enabled)
   - Forge detects the push
   - Runs deployment script automatically
   - Site updates in 1-2 minutes

3. **Manual deployment** (if Quick Deploy disabled)
   - Go to Forge
   - Click "Deploy Now"
   - Wait for completion

---

## 🛠️ Troubleshooting

### Issue: Deployment Fails

**Solution:**
1. Check deployment log in Forge
2. Look for error messages
3. Common issues:
   - Composer dependencies conflict
   - Migration errors
   - Permission issues

### Issue: 500 Error After Deploy

**Solution:**
```bash
# SSH into server
cd /home/forge/yourdomain.com

# Check logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check permissions
chmod -R 775 storage bootstrap/cache
```

### Issue: Assets Not Loading

**Solution:**
```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
```

### Issue: Database Connection Error

**Solution:**
1. Check `.env` database credentials
2. Verify database exists:
```bash
mysql -u forge -p
SHOW DATABASES;
```

---

## 📊 Monitoring & Maintenance

### Set Up Monitoring

1. **Laravel Telescope** (Development only!)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

2. **Error Tracking**
   - Sign up for Sentry.io
   - Add to your app
   - Get real-time error notifications

3. **Uptime Monitoring**
   - Use Oh Dear (https://ohdear.app)
   - Or UptimeRobot (free)

### Regular Maintenance

**Weekly:**
- Check error logs
- Review performance
- Check disk space

**Monthly:**
- Update dependencies
- Review security
- Check backups

**Commands:**
```bash
# Check disk space
df -h

# Check logs
tail -f storage/logs/laravel.log

# Update dependencies
composer update
npm update
```

---

## 🔐 Security Checklist

Before going live:

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong database password
- [ ] SSL certificate installed
- [ ] Force HTTPS enabled
- [ ] Firewall configured
- [ ] Backups enabled
- [ ] No test accounts
- [ ] Rate limiting enabled
- [ ] Security headers set

---

## 📞 Getting Help

### Forge Support
- Documentation: https://forge.laravel.com/docs
- Support: support@laravel.com
- Community: https://laracasts.com/discuss

### Your Repository
- Issues: https://github.com/arunenoah/XpenseSettle/issues
- Discussions: https://github.com/arunenoah/XpenseSettle/discussions

---

## 🎉 Success!

Your ExpenseSettle app is now live on Laravel Forge!

**Next Steps:**
1. Share with friends
2. Gather feedback
3. Add new features
4. Scale as needed

**Your deployment URL:**
- Production: `https://yourdomain.com`
- Forge Dashboard: `https://forge.laravel.com`
- GitHub Repo: `https://github.com/arunenoah/XpenseSettle`

---

## 📝 Quick Reference

### Common Commands

```bash
# Deploy manually
# In Forge UI: Click "Deploy Now"

# SSH into server
# In Forge UI: Click "SSH" button

# View logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan cache:clear

# Run migrations
php artisan migrate --force

# Restart PHP
# In Forge UI: Click "Restart PHP"
```

### Important URLs

- Forge Dashboard: https://forge.laravel.com
- Your Site: https://yourdomain.com
- GitHub Repo: https://github.com/arunenoah/XpenseSettle
- Server IP: Check in Forge

---

<div align="center">

**🚀 Happy Deploying!**

Made with ❤️ using Laravel Forge

</div>
