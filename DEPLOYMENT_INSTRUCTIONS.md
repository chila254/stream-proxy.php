# Stream Proxy Backend Deployment Instructions

## Overview
This directory contains PHP proxy files that need to be deployed to your web server to enable secure streaming with the Flutter application.

## Files to Deploy
1. `secure-proxy.php` - Main proxy for general streaming
2. `m3u8-proxy.php` - Specialized proxy for HLS streams
3. `sub-proxy.php` - Specialized proxy for subtitles
4. `stream-proxy.php` - Alternative streaming proxy with resume support

## Deployment Steps

### 1. Upload Files
Upload all PHP files to your web server's public directory (e.g., `htdocs` for InfinityFree hosting).

### 2. Set Permissions
Ensure the PHP files have the correct permissions (usually 644):
```bash
chmod 644 *.php
```

### 3. Configure Security Token
Update the security token in all PHP files to match your Flutter app configuration:
- In `secure-proxy.php`: Update `$valid_tokens` array
- In `m3u8-proxy.php`: Update `$allowed_token`
- In `sub-proxy.php`: Update `$allowed_token`
- In `stream-proxy.php`: Update `$valid_tokens` array

### 4. Configure Allowed Domains
Update the `$allowed_domains` array in `secure-proxy.php` to include all domains you want to allow streaming from.

### 5. Test Deployment
After deployment, test the proxy with:
```
curl "https://yourdomain.com/secure-proxy.php?health=1&token=your_secure_token"
```

You should receive a response like:
```json
{"status":"healthy","timestamp":"2023-01-01T00:00:00+00:00"}
```

## Troubleshooting

### Common Issues
1. **403 Forbidden**: Check that your token matches between the PHP files and Flutter app
2. **400 Bad Request**: Ensure you're passing a valid URL parameter
3. **500 Internal Server Error**: Check server error logs for PHP errors

### Debugging
Enable error reporting in PHP by adding to the top of each PHP file:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Security Considerations
1. Always use a strong, unique security token
2. Regularly rotate your security tokens
3. Monitor access logs for suspicious activity
4. Consider implementing rate limiting
5. Use HTTPS for all proxy requests

## Support
For deployment issues, check:
1. PHP version compatibility (PHP 7.4 or higher recommended)
2. Required PHP extensions: curl, fileinfo
3. Server memory limits for large file streaming
4. Firewall settings that might block outgoing requests
