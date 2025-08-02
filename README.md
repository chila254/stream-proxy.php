# Stream Proxy Backend

This directory contains the PHP proxy implementation for secure video streaming.

## Overview

The PHP proxies act as intermediaries between your Flutter app and video streaming sources. They provide several benefits:

- **Security**: Hides the real source of video content
- **Bypassing Restrictions**: Works around geo-restrictions and content blocking
- **Unified Interface**: Provides a consistent way to access different content sources
- **Download Support**: Enables secure downloading of video content
- **Resume Support**: Supports HTTP Range headers for resuming downloads

## Files

- `secure-proxy.php` - The main PHP proxy script (general purpose)
- `m3u8-proxy.php` - HLS Playlist & Segment Proxy
- `sub-proxy.php` - Subtitle (.vtt/.srt) Proxy
- `stream-proxy.php` - Streaming Proxy with Resume Support
- Other deployment files for various platforms

## Proxy Types

### 1. secure-proxy.php - General Purpose Proxy
Handles all types of content with resume support and redirects to specialized proxies when needed.

### 2. m3u8-proxy.php - HLS Playlist & Segment Proxy
Specifically designed for HLS streams, rewrites segment URLs to go through the proxy.

### 3. sub-proxy.php - Subtitle (.vtt/.srt) Proxy
Handles subtitle files with secure access and cross-origin support.

### 4. stream-proxy.php - Streaming Proxy with Resume Support
General-purpose streaming proxy with full HTTP Range header support.

## Deployment

### Requirements

- PHP 7.4 or higher
- PHP extensions: cURL, fileinfo
- Web server (Apache, Nginx, etc.)

### Deployment Steps

1. Upload all PHP proxy files to your web server
2. Configure your web server to serve the PHP files
3. Update the security tokens in the PHP files
4. Update the allowed domains list with your content sources

### Configuration

Edit `secure-proxy.php` to configure:

```php
// Security token validation
$valid_tokens = ['your_secure_token']; // Add your valid tokens here

// Allowed domains for content
$allowed_domains = [
    'example.com',
    'trusted-media-source.com',
    // Add your trusted domains here
];
```

## Usage

The proxies accept the following parameters:

- `url` - The URL of the content to proxy
- `token` - Security token for authentication
- `download` - Set to 1 for download requests

### Examples

#### General Streaming
```
GET https://yourdomain.com/secure-proxy.php?url=https://example.com/video.mp4&token=your_secure_token
```

#### Downloading
```
GET https://yourdomain.com/secure-proxy.php?url=https://example.com/video.mp4&token=your_secure_token&download=1
```

#### HLS Streaming (Direct)
```
GET https://yourdomain.com/m3u8-proxy.php?url=https://example.com/playlist.m3u8&token=your_secure_token
```

#### Subtitle Streaming (Direct)
```
GET https://yourdomain.com/sub-proxy.php?url=https://example.com/subtitles.vtt&token=your_secure_token
```

#### General Streaming with Resume Support (Direct)
```
GET https://yourdomain.com/stream-proxy.php?url=https://example.com/video.mp4&token=your_secure_token
```

#### Health Check
```
GET https://yourdomain.com/secure-proxy.php?health=1&token=your_secure_token
```

## Security

The proxies use token-based authentication to prevent unauthorized access. Make sure to:

1. Use a strong, unique token
2. Keep your token secret
3. Consider implementing additional security measures like IP whitelisting
4. Update the allowed domains list with your trusted content sources

## Support

For issues with the PHP proxies, check the server error logs and ensure:
- PHP is properly configured
- Required PHP extensions are installed (curl, fileinfo, etc.)
- File permissions are set correctly
- The server has internet access to fetch content
