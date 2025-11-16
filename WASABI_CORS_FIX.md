# Wasabi CORS Configuration Guide

## সমস্যা (Problem)
Frontend থেকে Wasabi bucket এ stored images load হচ্ছে না। Browser console এ CORS error দেখাচ্ছে।

## সমাধান (Solution)
Wasabi bucket এ CORS policy configure করতে হবে।

## পদ্ধতি ১: Wasabi Console থেকে (Recommended - সহজ)

### ধাপ ১: Wasabi Console Login
1. যান: https://console.wasabisys.com/
2. Login করুন credentials দিয়ে

### ধাপ ২: Bucket Settings
1. **Buckets** মেনু থেকে `oshongshoy` bucket select করুন
2. **Settings** tab এ যান
3. **CORS Configuration** section খুঁজুন

### ধাপ ৩: CORS Rules Add করুন
নিচের XML configuration paste করুন:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<CORSConfiguration xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
  <CORSRule>
    <AllowedOrigin>http://localhost:3000</AllowedOrigin>
    <AllowedOrigin>http://localhost:3003</AllowedOrigin>
    <AllowedOrigin>https://oshongshoy.com</AllowedOrigin>
    <AllowedOrigin>https://www.oshongshoy.com</AllowedOrigin>
    <AllowedMethod>GET</AllowedMethod>
    <AllowedMethod>HEAD</AllowedMethod>
    <AllowedMethod>PUT</AllowedMethod>
    <AllowedMethod>POST</AllowedMethod>
    <AllowedMethod>DELETE</AllowedMethod>
    <AllowedHeader>*</AllowedHeader>
    <ExposeHeader>ETag</ExposeHeader>
    <ExposeHeader>Content-Length</ExposeHeader>
    <ExposeHeader>Content-Type</ExposeHeader>
    <MaxAgeSeconds>3600</MaxAgeSeconds>
  </CORSRule>
</CORSConfiguration>
```

### ধাপ ৪: Save এবং Verify
1. **Save Changes** button এ click করুন
2. Browser এ page refresh করুন
3. Images এখন load হওয়া উচিত

---

## পদ্ধতি ২: AWS CLI দিয়ে (Advanced)

### Prerequisites
AWS CLI install করুন:
```bash
brew install awscli
```

### CORS Configuration Apply করুন
```bash
cd /Users/kamalahmed/react_projects/oshongshoy/oshongshoy-backend
./configure-wasabi-cors.sh
```

Script automatically:
- Wasabi credentials use করবে
- CORS configuration apply করবে
- Configuration verify করবে

---

## পদ্ধতি ৩: Manual AWS CLI Command

যদি script কাজ না করে, manually run করুন:

```bash
# Set credentials
export AWS_ACCESS_KEY_ID="TR41Q8K5LJFNAPAPQLHZ"
export AWS_SECRET_ACCESS_KEY="PMeC4kUd96BfAs0BGAvheTw95nzOiB9fnIeYH6Ij"
export AWS_DEFAULT_REGION="ap-southeast-1"

# Apply CORS
aws s3api put-bucket-cors \
  --bucket oshongshoy \
  --cors-configuration file://wasabi-cors-config.json \
  --endpoint-url https://s3.ap-southeast-1.wasabisys.com

# Verify CORS
aws s3api get-bucket-cors \
  --bucket oshongshoy \
  --endpoint-url https://s3.ap-southeast-1.wasabisys.com
```

---

## Troubleshooting

### Problem: Images still not loading after CORS configuration

**Solution 1: Clear browser cache**
```bash
# Chrome DevTools
1. Open DevTools (F12)
2. Right-click refresh button
3. Select "Empty Cache and Hard Reload"
```

**Solution 2: Check bucket policy**
Bucket policy নিশ্চিত করুন যে public read access আছে:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::oshongshoy/*"
    }
  ]
}
```

**Solution 3: Verify image URLs**
Console এ check করুন image URLs সঠিক format এ আছে কিনা:
```
https://s3.ap-southeast-1.wasabisys.com/oshongshoy/images/original/filename.jpg
```

---

## Expected Result

CORS configure করার পর:
- ✅ Frontend থেকে images load হবে
- ✅ Console এ CORS error থাকবে না
- ✅ Published articles এ images properly display হবে

---

## Support

যদি এখনও সমস্যা হয়:
1. Wasabi support contact করুন: support@wasabi.com
2. Bucket settings দুইবার check করুন
3. Browser cache clear করুন এবং retry করুন
