# Google Cloud Vision Setup - Two Methods

This guide shows you two ways to configure Google Cloud Vision credentials for the OCR feature.

## Method 1: JSON Credentials in .env (Recommended)

### Why Choose This Method?
✅ **No file management** - No need to store key files
✅ **Safer** - Credentials stay in environment
✅ **Easier deployment** - Just environment variables
✅ **Cloud-friendly** - Works with all cloud providers (Heroku, Railway, Vercel, etc.)

### How to Set Up

1. **Download Service Account Key**
   - Go to Google Cloud Console
   - APIs & Services → Credentials
   - Click on Service Account
   - Keys tab → Create JSON key
   - A JSON file downloads to your computer

2. **Get Credentials from JSON File**
   - Open downloaded JSON file in a text editor
   - Copy the entire JSON content

3. **Add to .env File**

   Option A: Direct JSON (pretty, readable):
   ```bash
   GOOGLE_CLOUD_CREDENTIALS={"type":"service_account","project_id":"my-project-id","private_key_id":"key123","private_key":"-----BEGIN PRIVATE KEY-----\nMIIEv...\n-----END PRIVATE KEY-----\n","client_email":"service@my-project.iam.gserviceaccount.com","client_id":"123456789","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://oauth2.googleapis.com/token","auth_provider_x509_cert_url":"https://www.googleapis.com/oauth2/v1/certs","client_x509_cert_url":"https://www.googleapis.com/robot/v1/metadata/x509/service%40my-project.iam.gserviceaccount.com"}
   ```

   Option B: Base64 Encoded (cleaner):
   ```bash
   # Encode the JSON file to base64
   # macOS/Linux: cat service-account-key.json | base64
   # Windows PowerShell: [Convert]::ToBase64String([IO.File]::ReadAllBytes('service-account-key.json'))

   GOOGLE_CLOUD_CREDENTIALS=ewoidHlwZSI6InNlcnZpY2VfYWNjb3VudCIsInByb2plY3RfaWQiOiJteS1wcm9qZWN0LWlkIiwicHJpdmF0ZV9rZXlfaWQiOiJrZXkxMjMiLCJwcml2YXRlX2tleSI6Ii0tLS0tQkVHSU4gUFJJVkFURSBLRVktLS0tLS4uLi0tLS0tRU5EIFBSSVZBVEUgS0VZLS0tLS0tIn0=
   ```

4. **Set Other Required Variables**
   ```bash
   GOOGLE_CLOUD_VISION_ENABLED=true
   GOOGLE_CLOUD_PROJECT_ID=my-project-id

   # Leave this empty if using direct credentials
   # GOOGLE_CLOUD_KEY_FILE=
   ```

5. **Done!** The system will automatically use the credentials from .env.

### Advantages
- No file system access needed
- Works in containerized environments
- Compatible with CI/CD pipelines
- Easy to rotate credentials
- No .gitignore management needed

### Disadvantages
- Long environment variable (but manageable)
- Need to base64 encode for cleaner look

---

## Method 2: Key File Path (Alternative)

### Why Choose This Method?
✅ **Simple** - Just point to a file
✅ **Secure** - File outside web root
✅ **Clear** - Easy to understand path
✅ **Traditional** - Familiar approach

### How to Set Up

1. **Download Service Account Key**
   - Go to Google Cloud Console
   - APIs & Services → Credentials
   - Click on Service Account
   - Keys tab → Create JSON key
   - A JSON file downloads

2. **Store Key File Securely**

   Option A: Inside project (development only):
   ```bash
   mkdir -p storage/keys
   cp /path/to/downloaded-key.json storage/keys/gcp-service-account.json
   ```

   Option B: Outside project (recommended for production):
   ```bash
   # Store in secure location
   /secure/keys/gcp-service-account.json
   ```

3. **Add to .env File**
   ```bash
   GOOGLE_CLOUD_VISION_ENABLED=true
   GOOGLE_CLOUD_PROJECT_ID=my-project-id
   GOOGLE_CLOUD_KEY_FILE=/absolute/path/to/gcp-service-account.json

   # Leave this empty
   # GOOGLE_CLOUD_CREDENTIALS=
   ```

4. **Secure the File**
   ```bash
   # Restrict file permissions
   chmod 600 /path/to/gcp-service-account.json

   # Add to .gitignore (if in project)
   echo "storage/keys/*.json" >> .gitignore
   ```

5. **Done!** The system will use credentials from the file.

### Advantages
- Traditional approach
- File can be shared securely
- Easier to understand visually
- Can use relative paths

### Disadvantages
- Need to manage file storage
- Need to secure file permissions
- Deployment complexity
- File system must be accessible

---

## Comparison Table

| Feature | Method 1 (Credentials in .env) | Method 2 (Key File) |
|---------|--------------------------------|---------------------|
| **Setup Time** | 5 minutes | 10 minutes |
| **Complexity** | Low | Medium |
| **File Management** | None | Required |
| **Security** | ✅ High | ⚠️ Medium |
| **Cloud Deploy** | ✅ Easy | ⚠️ Complex |
| **Development** | ✅ Easy | ✅ Easy |
| **Production** | ✅ Recommended | ⚠️ Alternative |
| **CI/CD** | ✅ Native | ⚠️ Extra steps |
| **Readability** | ⚠️ Long variable | ✅ Clear path |
| **Rotation** | ✅ Easy | ⚠️ Manual |

---

## Quick Setup Commands

### Method 1: For macOS/Linux Users

```bash
# 1. Download key file from Google Cloud Console

# 2. Extract JSON content and add to .env
CREDS=$(cat ~/Downloads/service-account-key.json)
echo "GOOGLE_CLOUD_CREDENTIALS=$CREDS" >> .env

# 3. Add other variables
echo "GOOGLE_CLOUD_VISION_ENABLED=true" >> .env
echo "GOOGLE_CLOUD_PROJECT_ID=your-project-id" >> .env

# 4. Verify
grep GOOGLE_CLOUD .env
```

### Method 1: For Windows Users (PowerShell)

```powershell
# 1. Download key file from Google Cloud Console

# 2. Read and encode
$json = Get-Content "C:\Downloads\service-account-key.json" -Raw
$base64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($json))

# 3. Add to .env
Add-Content .env "GOOGLE_CLOUD_CREDENTIALS=$base64"
Add-Content .env "GOOGLE_CLOUD_VISION_ENABLED=true"
Add-Content .env "GOOGLE_CLOUD_PROJECT_ID=your-project-id"

# 4. Verify
Select-String "GOOGLE_CLOUD" .env
```

### Method 2: For All Platforms

```bash
# 1. Download key file from Google Cloud Console

# 2. Create storage directory
mkdir -p storage/keys

# 3. Copy key file
cp ~/Downloads/service-account-key.json storage/keys/

# 4. Secure the file
chmod 600 storage/keys/service-account-key.json

# 5. Add to .env
echo "GOOGLE_CLOUD_VISION_ENABLED=true" >> .env
echo "GOOGLE_CLOUD_PROJECT_ID=your-project-id" >> .env
echo "GOOGLE_CLOUD_KEY_FILE=$(pwd)/storage/keys/service-account-key.json" >> .env

# 6. Add to .gitignore
echo "storage/keys/" >> .gitignore

# 7. Verify
grep GOOGLE_CLOUD .env
```

---

## Troubleshooting

### "Credentials not found"

If you see this error:

```
Google Cloud Vision not configured.
Set either GOOGLE_CLOUD_CREDENTIALS in .env or GOOGLE_CLOUD_KEY_FILE path.
```

**Check:**
1. Is `GOOGLE_CLOUD_VISION_ENABLED=true`?
2. Is one of these set?
   - `GOOGLE_CLOUD_CREDENTIALS` (Method 1)
   - `GOOGLE_CLOUD_KEY_FILE` (Method 2)

```bash
# Verify in .env
grep GOOGLE_CLOUD_CREDENTIALS .env
grep GOOGLE_CLOUD_KEY_FILE .env
```

### "Invalid JSON in credentials"

If you get JSON parsing error:

**For Method 1:** Verify JSON is complete
```bash
# Test parsing
php -r "echo json_decode(getenv('GOOGLE_CLOUD_CREDENTIALS')) ? 'Valid' : 'Invalid';"
```

**For Method 2:** Verify file exists and is readable
```bash
# Check file exists
ls -la /path/to/key.json

# Check readable
cat /path/to/key.json | head -5
```

### "File not found" (Method 2)

**Solution:** Use absolute path
```bash
# Don't use: storage/keys/file.json
# Do use: /full/path/to/storage/keys/file.json

# Get absolute path
pwd  # current directory
ls -la storage/keys/  # verify file exists
```

### "Permission denied" (Method 2)

**Solution:** Fix file permissions
```bash
chmod 600 /path/to/gcp-service-account.json
chmod 700 $(dirname /path/to/gcp-service-account.json)
```

---

## Deploying to Production

### Heroku / Railway / Render (Recommend Method 1)

```bash
# Using Method 1 (Credentials in .env)
heroku config:set GOOGLE_CLOUD_CREDENTIALS="$(cat service-account-key.json)"
heroku config:set GOOGLE_CLOUD_VISION_ENABLED=true
heroku config:set GOOGLE_CLOUD_PROJECT_ID=your-project-id
```

### AWS Lambda / EC2 (Method 1 or 2)

**Method 1 - Secure:**
```bash
# Store in AWS Secrets Manager
aws secretsmanager create-secret \
  --name google-cloud-credentials \
  --secret-string file://service-account-key.json

# Reference in app
heroku config:set GOOGLE_CLOUD_CREDENTIALS='...' # from secrets
```

**Method 2 - Simple:**
```bash
# Store in /tmp or /opt
aws s3 cp service-account-key.json s3://my-bucket/keys/
```

### Docker (Method 1 Preferred)

```dockerfile
# Method 1 - Environment variable
ENV GOOGLE_CLOUD_CREDENTIALS=${GOOGLE_CLOUD_CREDENTIALS}
ENV GOOGLE_CLOUD_VISION_ENABLED=true
ENV GOOGLE_CLOUD_PROJECT_ID=${GOOGLE_CLOUD_PROJECT_ID}

# Method 2 - Volume mount (if using files)
VOLUME ["/app/secrets"]
```

### GitHub Actions / CI-CD (Method 1)

```yaml
# Set in GitHub Secrets
# Then use in workflow:

env:
  GOOGLE_CLOUD_VISION_ENABLED: true
  GOOGLE_CLOUD_PROJECT_ID: ${{ secrets.GCP_PROJECT_ID }}
  GOOGLE_CLOUD_CREDENTIALS: ${{ secrets.GCP_CREDENTIALS }}
```

---

## Security Best Practices

### Never Do This ❌

```bash
# ❌ Don't commit key file
git add storage/keys/service-account-key.json
git commit -m "Add credentials"

# ❌ Don't hardcode in code
const CREDENTIALS = '{"type":"service_account",...}';

# ❌ Don't log credentials
Log::info('Using credentials: ' . env('GOOGLE_CLOUD_CREDENTIALS'));

# ❌ Don't share credentials in email/chat
```

### Always Do This ✅

```bash
# ✅ Use .gitignore
echo "storage/keys/*.json" >> .gitignore
echo ".env" >> .gitignore

# ✅ Use environment variables
env('GOOGLE_CLOUD_CREDENTIALS')

# ✅ Rotate credentials regularly
# Go to Google Cloud Console → Regenerate keys

# ✅ Use least privilege
# Service account should only have Vision API access

# ✅ Store securely in production
# Use AWS Secrets Manager, Azure Key Vault, etc.
```

---

## Choosing Your Method

**Use Method 1 if you:**
- ✅ Deploying to cloud platforms (Heroku, Railway, etc.)
- ✅ Using Docker/containers
- ✅ Want simple deployment
- ✅ Don't want file management
- ✅ Prefer environment variable config

**Use Method 2 if you:**
- ✅ Running on traditional servers (VPS, dedicated)
- ✅ Want file-based security
- ✅ Prefer file permissions control
- ✅ Have secure file storage already
- ✅ Need to share keys securely with team

---

## Summary

| Scenario | Recommended |
|----------|-------------|
| **Local development** | Method 1 or 2 (both work) |
| **Cloud deployment** | Method 1 ✅ |
| **Traditional servers** | Method 2 ✅ |
| **Containerized** | Method 1 ✅ |
| **CI/CD pipeline** | Method 1 ✅ |
| **Team sharing** | Method 1 ✅ |
| **Security-first** | Method 1 ✅ (in Secrets Manager) |

---

**Recommended: Method 1 (JSON Credentials in .env)**
Easy setup, secure, cloud-ready, deployment-friendly.

**Questions?** See `.env.ocr.example` for complete variable reference.
