<?php
// stream-proxy.php - A secure proxy for streaming media content with full resume support
// This script acts as a proxy to fetch and stream media content securely with HTTP Range support

// Security headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Range');
header('Accept-Ranges: bytes');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Security token validation
$valid_tokens = ['xK9mP2qR7sT4vW8yZ3aB6cE9fH1jL5nQ']; // Unique secure token for maxstream.liveblog365.com
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

if (!in_array($token, $valid_tokens)) {
    http_response_code(401);
    exit('Invalid or missing token');
}

// Get parameters
$url = isset($_GET['url']) ? $_GET['url'] : (isset($_POST['url']) ? $_POST['url'] : '');
$download = isset($_GET['download']) ? $_GET['download'] : (isset($_POST['download']) ? $_POST['download'] : false);

if (empty($url)) {
    http_response_code(400);
    exit('Missing URL parameter');
}

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid URL format');
}

/**
 * Stream a file with full resume support using HTTP Range headers
 * 
 * @param string $url The URL of the file to stream
 * @param bool $isDownload Whether this is a download request
 */
function streamFileWithResume($url, $isDownload = false) {
    // Get request headers
    $headers = getallheaders();
    $range = isset($headers['Range']) ? $headers['Range'] : '';
    
    // Get file information
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $head = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        http_response_code($httpCode);
        exit('Failed to access file');
    }
    
    // Set appropriate headers
    $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
    $extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';
    
    // Determine MIME type based on file extension
    $mimeTypes = array(
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'm4a' => 'audio/mp4',
        'ts' => 'video/MP2T',
        'm3u8' => 'application/vnd.apple.mpegurl'
    );
    
    $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    
    if ($isDownload) {
        $filename = isset($pathInfo['filename']) ? $pathInfo['filename'] : 'video';
        if ($extension) $filename .= '.' . $extension;
        header('Content-Disposition: attachment; filename="' . $filename . '"');
    }
    
    // Handle range requests
    if ($range) {
        // Parse range header (e.g., "bytes=0-1023")
        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = intval($matches[1]);
            $end = ($matches[2] === '') ? $contentLength - 1 : intval($matches[2]);
            
            // Validate range
            if ($start >= $contentLength || $end >= $contentLength || $start > $end) {
                header("HTTP/1.1 416 Range Not Satisfiable");
                header("Content-Range: bytes */$contentLength");
                exit();
            }
            
            // Set partial content headers
            header("HTTP/1.1 206 Partial Content");
            header("Content-Range: bytes $start-$end/$contentLength");
            header("Content-Length: " . ($end - $start + 1));
            
            // Stream the partial content
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Range: bytes=' . $start . '-' . $end,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ));
            curl_exec($ch);
            curl_close($ch);
        } else {
            // Invalid range format
            header("HTTP/1.1 416 Range Not Satisfiable");
            header("Content-Range: bytes */$contentLength");
            exit();
        }
    } else {
        // Stream the entire file
        header("Content-Length: $contentLength");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_exec($ch);
        curl_close($ch);
    }
}

// Handle download requests with resume support
if ($download) {
    streamFileWithResume($url, true);
    exit();
}

// Handle streaming requests with resume support
streamFileWithResume($url, false);
exit();

// Health check endpoint
if (isset($_GET['health'])) {
    header('Content-Type: application/json');
    echo json_encode(array('status' => 'healthy', 'timestamp' => date('c')));
    exit();
}
?>
