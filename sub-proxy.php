<?php
// sub-proxy.php - Subtitle (.vtt/.srt) Proxy
// This script proxies subtitle files with token-based access and range request support

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

if (!$url || !preg_match('/\.(vtt|srt|ass)$/i', $url)) {
    http_response_code(400);
    exit('Invalid subtitle URL - Must be .vtt, .srt, or .ass file');
}

// Get request headers for range requests
$headers = getallheaders();
$range = isset($headers['Range']) ? $headers['Range'] : '';

// Fetch subtitle content with range support
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Range: ' . $range
));

$subtitle = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
curl_close($ch);

if ($subtitle === false && $httpCode !== 206) {
    http_response_code(500);
    exit('Failed to fetch subtitle');
}

// Set appropriate content type based on file extension
$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
switch($ext) {
    case 'vtt':
        header('Content-Type: text/vtt; charset=utf-8');
        break;
    case 'srt':
        header('Content-Type: text/plain; charset=utf-8');
        break;
    case 'ass':
        header('Content-Type: text/plain; charset=utf-8');
        break;
    default:
        header('Content-Type: text/plain; charset=utf-8');
}

// Handle range requests
if ($range) {
    header("HTTP/1.1 206 Partial Content");
    
    // Parse range header (e.g., "bytes=0-1023")
    if (preg_match('/bytes=(\d+)?-(\d+)?/', $range, $matches)) {
        $start = isset($matches[1]) ? intval($matches[1]) : 0;
        $end = isset($matches[2]) ? intval($matches[2]) : ($contentLength - 1);
        
        // Set Content-Range header
        header("Content-Range: bytes $start-$end/$contentLength");
        header("Content-Length: " . ($end - $start + 1));
    }
} else {
    // Set Content-Length for full content
    if ($contentLength > 0) {
        header("Content-Length: $contentLength");
    }
}

// Add cache headers for subtitles
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

echo $subtitle;
?>
