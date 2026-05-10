<?php

/**
 * Generate or retrieve existing CAPTCHA challenge
 * Returns array with image token, hint about length, and metadata
 */
function captcha_get_or_create(string $context): array
{
    $key = 'captcha_' . $context;
    $current = $_SESSION[$key] ?? null;

    if (is_array($current) && isset($current['token'], $current['answer_hash'], $current['expires_at'])) {
        if ((int)$current['expires_at'] >= time()) {
            return $current;
        }
    }

    // Generate 5-6 character alphanumeric string
    $answer = captcha_generate_string(5);
    
    $captcha = [
        'token' => bin2hex(random_bytes(16)), // Unique token for image endpoint
        'answer' => $answer,
        'answer_hash' => hash('sha256', strtolower($answer)),
        'expires_at' => time() + 900, // 15 minutes
        'attempts' => 0,
    ];

    $_SESSION[$key] = $captcha;
    return $captcha;
}

/**
 * Generate random alphanumeric string (excludes confusing chars: 0/O, 1/l/I, etc.)
 */
function captcha_generate_string(int $length = 5): string
{
    $chars = 'ABCDEFGHJKMNPQRSTVWXYZ23456789'; // Exclude: I, L, O, U, 0, 1
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $string;
}

/**
 * Generate CAPTCHA image with distorted text
 * Returns image resource or PNG binary
 */
function captcha_generate_image(string $text): string
{
    $width = 320;
    $height = 100;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    if (!$image) {
        throw new RuntimeException('Failed to create image');
    }

    // Define colors
    $bgColor = imagecolorallocate($image, 240, 245, 250); // Light blue background
    $textColor = imagecolorallocate($image, 15, 35, 70);   // Dark blue text
    $lineColor = imagecolorallocate($image, 180, 200, 220); // Medium gray for subtle lines
    $noiseColor = imagecolorallocate($image, 220, 225, 235); // Very light gray for noise
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Add subtle noise lines (fewer and lighter)
    for ($i = 0; $i < 3; $i++) {
        imageline(
            $image,
            random_int(0, $width),
            random_int(0, $height),
            random_int(0, $width),
            random_int(0, $height),
            $lineColor
        );
    }
    
    // Add subtle random dots
    for ($i = 0; $i < 40; $i++) {
        imagesetpixel(
            $image,
            random_int(0, $width),
            random_int(0, $height),
            $noiseColor
        );
    }
    
    // Render text
    captcha_render_distorted_text($image, $text, $textColor, $width, $height);
    
    // Output image as PNG
    ob_start();
    imagepng($image, null, 9);
    $imageData = ob_get_clean();
    imagedestroy($image);
    
    return $imageData ?? '';
}

/**
 * Render text on image with subtle distortion
 */
function captcha_render_distorted_text($image, string $text, int $textColor, int $width, int $height): void
{
    $fontSize = 5; // Built-in font size (1-5), using largest
    $charWidth = imagefontwidth($fontSize);
    $charHeight = imagefontheight($fontSize);
    
    // Increase spacing between characters for larger appearance
    $charSpacing = (int)($charWidth * 1.4);
    
    // Calculate starting position (center the text)
    $totalWidth = strlen($text) * $charSpacing;
    $startX = max(15, (int)(($width - $totalWidth) / 2));
    $baseY = (int)(($height - $charHeight) / 2) - 4;
    
    // Draw each character with minimal distortion
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        $charX = $startX + ($i * $charSpacing);
        
        // Very subtle vertical offset (wave effect)
        $yOffset = (int)(sin($i / strlen($text) * pi() * 1.5) * 2);
        $charY = $baseY + $yOffset + random_int(-1, 1);
        
        // Draw character with slight bold effect (double render with offset)
        imagestring($image, $fontSize, $charX, $charY, $char, $textColor);
        imagestring($image, $fontSize, $charX + 1, $charY, $char, $textColor);
    }
}

/**
 * Validate CAPTCHA response
 */
function captcha_validate(string $context, string $input): bool
{
    $key = 'captcha_' . $context;
    $current = $_SESSION[$key] ?? null;

    if (!is_array($current)) {
        return false;
    }

    if ((int)($current['expires_at'] ?? 0) < time()) {
        unset($_SESSION[$key]);
        return false;
    }

    // Check max attempts (5)
    $current['attempts'] = ($current['attempts'] ?? 0) + 1;
    if ($current['attempts'] > 5) {
        unset($_SESSION[$key]);
        return false;
    }
    $_SESSION[$key] = $current;

    // Normalize and validate input
    $normalized = strtolower(trim(preg_replace('/\s+/', '', $input)));
    $ok = hash_equals((string)$current['answer_hash'], hash('sha256', (string)$normalized));

    if ($ok) {
        unset($_SESSION[$key]);
    }

    return $ok;
}

/**
 * Reset CAPTCHA for a context
 */
function captcha_reset(string $context): void
{
    unset($_SESSION['captcha_' . $context]);
}

/**
 * Get image URL for CAPTCHA
 */
function captcha_image_url(string $context): string
{
    return base_path('/captcha/image?context=' . urlencode($context) . '&t=' . time());
}
