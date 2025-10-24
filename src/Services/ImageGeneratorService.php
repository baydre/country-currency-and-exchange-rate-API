<?php

namespace App\Services;

use App\Models\Country;

/**
 * Service for generating summary images using PHP GD
 */
class ImageGeneratorService
{
    private $countryModel;
    private $cacheDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countryModel = new Country();
        $this->cacheDir = basePath(env('CACHE_DIR', 'cache'));
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Generate summary image with country statistics
     *
     * @return string Path to generated image
     * @throws \RuntimeException
     */
    public function generateSummary()
    {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is not available');
        }

        // Fetch data
        $totalCountries = $this->countryModel->count();
        $topCountries = $this->countryModel->topByGdp(5);
        $status = $this->countryModel->getApiStatus();
        $lastRefreshed = $status['last_refreshed_at'] ?? 'Never';

        // Image dimensions
        $width = 800;
        $height = 600;

        // Create image
        $image = imagecreatetruecolor($width, $height);
        
        if (!$image) {
            throw new \RuntimeException('Failed to create image');
        }

        // Define colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $darkGray = imagecolorallocate($image, 50, 50, 50);
        $lightGray = imagecolorallocate($image, 200, 200, 200);
        $blue = imagecolorallocate($image, 41, 128, 185);
        $green = imagecolorallocate($image, 39, 174, 96);

        // Fill background
        imagefill($image, 0, 0, $white);

        // Draw header background
        imagefilledrectangle($image, 0, 0, $width, 80, $blue);

        // Draw title
        $titleFont = 5; // Built-in font (1-5)
        $title = "Country & Currency Summary";
        $titleWidth = imagefontwidth($titleFont) * strlen($title);
        $titleX = ($width - $titleWidth) / 2;
        imagestring($image, $titleFont, $titleX, 30, $title, $white);

        // Draw total countries
        $y = 120;
        $font = 4;
        
        imagestring($image, $font, 50, $y, "Total Countries: " . $totalCountries, $darkGray);
        
        // Draw horizontal line
        $y += 40;
        imageline($image, 50, $y, $width - 50, $y, $lightGray);
        
        // Draw "Top 5 by GDP" header
        $y += 30;
        imagestring($image, $font, 50, $y, "Top 5 Countries by Estimated GDP:", $blue);
        
        // Draw top countries
        $y += 40;
        $rank = 1;
        foreach ($topCountries as $country) {
            $gdp = number_format($country['estimated_gdp'], 2);
            $text = "{$rank}. {$country['name']} - \${$gdp}";
            
            imagestring($image, 3, 70, $y, $text, $darkGray);
            $y += 30;
            $rank++;
        }

        // If less than 5 countries, show message
        if (count($topCountries) === 0) {
            imagestring($image, 3, 70, $y, "No data available yet", $lightGray);
        }

        // Draw footer with timestamp
        $y = $height - 60;
        imageline($image, 50, $y, $width - 50, $y, $lightGray);
        
        $y += 20;
        $footerText = "Last Refreshed: " . $lastRefreshed;
        imagestring($image, 3, 50, $y, $footerText, $green);

        // Save image
        $imagePath = $this->cacheDir . '/summary.png';
        $success = imagepng($image, $imagePath);
        
        // Free memory
        imagedestroy($image);

        if (!$success) {
            throw new \RuntimeException('Failed to save image');
        }

        return $imagePath;
    }

    /**
     * Get path to summary image
     *
     * @return string|null
     */
    public function getSummaryImagePath()
    {
        $imagePath = $this->cacheDir . '/summary.png';
        
        return file_exists($imagePath) ? $imagePath : null;
    }
}
