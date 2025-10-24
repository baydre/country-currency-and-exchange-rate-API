<?php

namespace App\Controllers;

use App\Services\ImageGeneratorService;

/**
 * Image Controller - Handle image serving requests
 */
class ImageController
{
    private $imageGenerator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->imageGenerator = new ImageGeneratorService();
    }

    /**
     * GET /countries/image
     * Serve the summary image
     *
     * @return never
     */
    public function show()
    {
        try {
            $imagePath = $this->imageGenerator->getSummaryImagePath();
            
            if (!$imagePath || !file_exists($imagePath)) {
                jsonResponse([
                    'error' => 'Image not found',
                    'message' => 'Summary image has not been generated yet. Please run POST /countries/refresh first.'
                ], 404);
            }

            // Set headers for image
            header('Content-Type: image/png');
            header('Content-Length: ' . filesize($imagePath));
            header('Cache-Control: public, max-age=3600');
            
            // Output image
            readfile($imagePath);
            exit;
            
        } catch (\Exception $e) {
            jsonResponse([
                'error' => 'Internal server error',
                'message' => env('APP_DEBUG') ? $e->getMessage() : 'Failed to serve image'
            ], 500);
        }
    }
}
