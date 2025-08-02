<?php
// m3u8-proxy.php - HLS Playlist & Segment Proxy
// This script proxies .m3u8 files and rewrites segment URLs to go through the proxy

$token = isset($_GET['token']) ? $_GET['token'] : '';
$allowed_token = 'xK9mP2qR7sT4vW8yZ3aB6cE9fH1jL5nQ'; // Unique secure token for maxstream.liveblog365.com

// Security headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Range');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($token !== $allowed_token) {
    http_response_code(403);
    exit('Access Denied');
}

$url = isset($_GET['url']) ? $_GET['url'] : '';
$segment = isset($_GET['segment']) ? $_GET['segment'] : '';

/**
 * Stream a file with proper range request support
 * 
 * @param string $url The URL of the file to stream
 */
function streamFile($url) {
    // Add support for range requests
    $headers = getallheaders();
    $range = isset($headers['Range']) ? $headers['Range'] : '';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Range: ' . $range
    ));
    
    // If resume is requested
    if ($range) {
        header("HTTP/1.1 206 Partial Content");
        // Get remote headers to pass through content-range
        $header_ch = curl_init($url);
        curl_setopt($header_ch, CURLOPT_NOBODY, true);
        curl_setopt($header_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($header_ch, CURLOPT_HEADER, true);
        curl_setopt($header_ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($header_ch, CURLOPT_HTTPHEADER, array('Range: ' . $range));
        $remoteHeaders = curl_exec($header_ch);
        curl_close($header_ch);
        
        // Extract Content-Range header
        if (preg_match("/Content-Range: (.*)/i", $remoteHeaders, $match)) {
            header("Content-Range: " . trim($match[1]));
        }
    }
    
    // Set appropriate content type
    $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
    $extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';
    
    switch($extension) {
        case 'ts':
            header('Content-Type: video/MP2T');
            break;
        case 'm3u8':
            header('Content-Type: application/vnd.apple.mpegurl');
            break;
        default:
            // Try to detect MIME type
            $mimeType = mime_content_type(basename(parse_url($url, PHP_URL_PATH))) ?: 'video/MP2T';
            header("Content-Type: $mimeType");
    }
    
    header("Accept-Ranges: bytes");
    
    curl_exec($ch);
    curl_close($ch);
}

// Serve TS segment if requested
if ($segment) {
    header('Content-Type: video/MP2T');
    streamFile(urldecode($segment));
    exit;
}

// Validate URL
if (!$url || strpos($url, '.m3u8') === false) {
    http_response_code(400);
    exit('Invalid URL - Must be an .m3u8 file');
}

// Fetch and rewrite M3U8 file
$playlist = file_get_contents($url);
if ($playlist === false) {
    http_response_code(500);
    exit('Failed to load playlist');
}

// Parse base URL for relative paths
$base = dirname($url);

// Rewrite segment URLs to go through this proxy
$rewritten = preg_replace_callback('/^(?!#)(.*\.(ts|aac|mp4))/mi', function($matches) use ($base, $token) {
    $segmentPath = $matches[1];
    
    // Handle absolute URLs
    if (strpos($segmentPath, 'http') === 0) {
        $segmentUrl = $segmentPath;
    } else {
        // Handle relative URLs
        $segmentUrl = $base . '/' . ltrim($segmentPath, '/');
    }
    
    // URL encode the segment URL for safe passing as parameter
    $encodedSegmentUrl = urlencode($segmentUrl);
    
    // Return the rewritten URL pointing to this proxy
    return "m3u8-proxy.php?segment=" . $encodedSegmentUrl . "&token=" . $token;
}, $playlist);

header('Content-Type: application/vnd.apple.mpegurl');
echo $rewritten;
?>
