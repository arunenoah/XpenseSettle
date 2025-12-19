<?php

namespace App\Services;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * OcrService: Handles OCR (Optical Character Recognition) functionality
 * using Google Cloud Vision API for extracting expense data from receipts
 * and invoices.
 */
class OcrService
{
    private ?ImageAnnotatorClient $client = null;

    /**
     * Initialize OCR service and check if it's enabled.
     */
    public function __construct()
    {
        // Only initialize client if explicitly enabled
        // This prevents errors if config is not set
    }

    /**
     * Check if Google Cloud Vision OCR is enabled.
     */
    public function isEnabled(): bool
    {
        return config('googlecloud.vision.enabled', false) === true;
    }

    /**
     * Initialize Google Cloud Vision client.
     *
     * Supports two configuration methods:
     * 1. Key file path: GOOGLE_CLOUD_KEY_FILE=/path/to/service-account-key.json
     * 2. JSON credentials in .env: GOOGLE_CLOUD_CREDENTIALS={json from service account}
     *
     * NOTE: You MUST use Service Account credentials, not OAuth2 credentials!
     * OAuth2 credentials (with client_secret) will NOT work.
     *
     * @throws \Exception
     */
    private function initializeClient(): void
    {
        try {
            $keyFile = config('googlecloud.vision.key_file');

            // Verify key file exists and is readable
            if (!$keyFile || !file_exists($keyFile)) {
                throw new \Exception(
                    'Google Cloud Vision key file not found at: ' . ($keyFile ?: 'not configured') .
                    '. Set GOOGLE_CLOUD_KEY_FILE in .env with absolute path to service account JSON file.'
                );
            }

            // Verify it's readable
            if (!is_readable($keyFile)) {
                throw new \Exception(
                    'Google Cloud Vision key file is not readable: ' . $keyFile .
                    '. Check file permissions.'
                );
            }

            // Read and validate the JSON
            $jsonContent = file_get_contents($keyFile);
            $credentials = json_decode($jsonContent, true);

            if (!$credentials) {
                throw new \Exception(
                    'Invalid JSON in Google Cloud Vision key file: ' . $keyFile
                );
            }

            // Validate it's a service account, not OAuth2
            if (!isset($credentials['type']) || $credentials['type'] !== 'service_account') {
                throw new \Exception(
                    'Invalid credentials file: Must use Service Account credentials, not OAuth2. ' .
                    'The JSON must have "type": "service_account" field. ' .
                    'Download from Google Cloud Console → Service Accounts (not OAuth2 credentials).'
                );
            }

            // Set environment variable for Google Auth library
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyFile);

            // Initialize client - it will use the environment variable
            $this->client = new ImageAnnotatorClient();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Cloud Vision client: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract text and expense information from an image.
     *
     * @param UploadedFile|string $imageSource File or base64 string
     * @param string|null $cacheKey Optional cache key for caching results
     * @return array Extracted expense data including items, amounts, vendor, etc.
     * @throws \Exception
     */
    public function extractExpenseData($imageSource, ?string $cacheKey = null): array
    {
        if (!$this->isEnabled()) {
            throw new \Exception('OCR service is not enabled. Please configure Google Cloud Vision credentials in .env');
        }

        // Initialize client only when extraction is needed
        if ($this->client === null) {
            $this->initializeClient();
        }

        // Check cache first
        if ($cacheKey && config('googlecloud.ocr.cache_results')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info('Returning cached OCR result for key: ' . $cacheKey);
                return $cached;
            }
        }

        try {
            // Get image data
            $imageData = $this->getImageData($imageSource);

            // Create Vision API request
            $image = new Image();
            $image->setContent($imageData);

            // Set up features for text detection
            $feature = new Feature();
            $feature->setType(Type::TEXT_DETECTION);

            // Create AnnotateImageRequest properly
            $annotateImageRequest = new AnnotateImageRequest();
            $annotateImageRequest->setImage($image);
            $annotateImageRequest->setFeatures([$feature]);

            // Create request
            $request = new BatchAnnotateImagesRequest();
            $request->setRequests([$annotateImageRequest]);

            // Perform text detection
            $response = $this->client->batchAnnotateImages($request);

            // Extract results
            $results = $response->getResponses();
            if (empty($results)) {
                throw new \Exception('No response from Google Cloud Vision API');
            }

            $result = $results[0];

            if ($result->hasError()) {
                throw new \Exception(
                    'Google Cloud Vision API error: ' . $result->getError()->getMessage()
                );
            }

            // Parse detected text into structured expense data
            $extractedData = $this->parseTextToExpenseData(
                $result->getTextAnnotations()
            );

            // Cache the result
            if ($cacheKey && config('googlecloud.ocr.cache_results')) {
                Cache::put(
                    $cacheKey,
                    $extractedData,
                    config('googlecloud.ocr.cache_ttl')
                );
            }

            return $extractedData;
        } catch (\Exception $e) {
            Log::error('OCR extraction failed: ' . $e->getMessage());
            throw new \Exception('Failed to extract expense data from image: ' . $e->getMessage());
        }
    }

    /**
     * Get image data from uploaded file or string.
     * Returns raw binary content, not base64.
     *
     * @param UploadedFile|string $imageSource
     * @return string Raw binary image data
     * @throws \Exception
     */
    private function getImageData($imageSource): string
    {
        if ($imageSource instanceof UploadedFile) {
            // Validate file
            $this->validateImage($imageSource);

            // Read file content - return raw bytes, not base64
            $content = file_get_contents($imageSource->getRealPath());
            if ($content === false) {
                throw new \Exception('Failed to read image file.');
            }

            return $content;
        }

        if (is_string($imageSource)) {
            // Check if it's a file path
            if (file_exists($imageSource)) {
                $content = file_get_contents($imageSource);
                if ($content === false) {
                    throw new \Exception('Failed to read image file at path: ' . $imageSource);
                }
                return $content;
            }

            // If it's base64, decode it to get raw bytes
            if (base64_encode(base64_decode($imageSource, true)) === $imageSource) {
                $decoded = base64_decode($imageSource, true);
                if ($decoded === false) {
                    throw new \Exception('Failed to decode base64 image data.');
                }
                return $decoded;
            }

            throw new \Exception('Invalid image source provided.');
        }

        throw new \Exception('Image source must be UploadedFile or string.');
    }

    /**
     * Validate image before processing.
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    private function validateImage(UploadedFile $file): void
    {
        $maxSize = config('googlecloud.ocr.max_file_size');
        if ($file->getSize() > $maxSize) {
            throw new \Exception(
                'Image file size (' . $file->getSize() . ' bytes) exceeds maximum allowed size (' . $maxSize . ' bytes).'
            );
        }

        $allowedFormats = config('googlecloud.ocr.supported_formats', ['jpeg', 'png', 'gif', 'bmp', 'webp']);
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedFormats)) {
            throw new \Exception(
                'Unsupported image format: ' . $extension . '. Allowed formats: ' . implode(', ', $allowedFormats)
            );
        }
    }

    /**
     * Parse detected text annotations into structured expense data.
     *
     * @param array|\Google\Protobuf\RepeatedField $textAnnotations Google Cloud Vision text annotations
     * @return array Structured expense data
     */
    private function parseTextToExpenseData($textAnnotations): array
    {
        // Convert RepeatedField to array if needed
        if (!is_array($textAnnotations)) {
            $textAnnotations = iterator_to_array($textAnnotations);
        }

        if (empty($textAnnotations)) {
            return [
                'raw_text' => '',
                'items' => [],
                'total_amount' => null,
                'vendor' => null,
                'confidence' => 0,
                'parse_status' => 'no_text_detected',
            ];
        }

        // First annotation is the full text
        $fullText = $textAnnotations[0]->getDescription() ?? '';
        $confidence = $textAnnotations[0]->getConfidence() ?? 0;

        // Extract individual text blocks for better parsing
        $textBlocks = [];
        foreach ($textAnnotations as $index => $annotation) {
            if ($index === 0) continue; // Skip full text
            $textBlocks[] = $annotation->getDescription();
        }

        // Parse the full text to extract expense details
        $expenseData = [
            'raw_text' => $fullText,
            'items' => $this->extractLineItems($textBlocks, $fullText),
            'total_amount' => $this->extractTotalAmount($textBlocks, $fullText),
            'vendor' => $this->extractVendor($textBlocks, $fullText),
            'date' => $this->extractDate($textBlocks, $fullText),
            'confidence' => (float) $confidence,
            'parse_status' => 'success',
        ];

        return $expenseData;
    }

    /**
     * Extract line items from receipt text.
     *
     * @param array $textBlocks Individual text blocks
     * @param string $fullText Complete text
     * @return array Array of line items with descriptions and amounts
     */
    private function extractLineItems(array $textBlocks, string $fullText): array
    {
        $items = [];
        $lines = explode("\n", $fullText);

        // Look for the TAX INVOICE or items section
        $inItemsSection = false;
        $itemCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Detect start of items section
            if (preg_match('/TAX\s+INVOICE|Tax\s+Description|^Description/i', $line)) {
                $inItemsSection = true;
                continue;
            }

            // Detect end of items section
            if (preg_match('/^Total:|Subtotal:|TOTAL|Change:|Payment/i', $line)) {
                $inItemsSection = false;
            }

            // Only process lines in the items section
            if (!$inItemsSection) {
                continue;
            }

            // Skip header lines
            if (preg_match('/^(Tax|Qty|Amount|Description|-----)/i', $line)) {
                continue;
            }

            // Skip if line has no letters (pure numbers/symbols)
            if (!preg_match('/[a-z]/i', $line)) {
                continue;
            }

            $itemCount++;
            if ($itemCount > 50) {
                break;
            }

            // Try to extract amount from the line
            $amount = null;
            if (preg_match('/[\$₹€£¥]?\s*([\d,]+\.?\d+)\s*$/', $line, $matches)) {
                $amount = (float) str_replace(',', '', $matches[1]);
                // Remove price from line for cleaner description
                $line = preg_replace('/\s*[\$₹€£¥]?\s*([\d,]+\.?\d+)\s*$/', '', $line);
            }

            $description = trim($line);
            if (!empty($description) && strlen($description) > 2) {
                $items[] = [
                    'description' => substr($description, 0, 255),
                    'amount' => $amount,
                ];
            }
        }

        return $items;
    }

    /**
     * Check if a line is likely a line item from a receipt.
     *
     * @param string $line
     * @return bool
     */
    private function isLikelyLineItem(string $line): bool
    {
        $line = trim($line);

        // Skip empty lines
        if (empty($line)) {
            return false;
        }

        // Skip lines that are likely headers or footers
        $skipPatterns = [
            '/^(Date|Time|Total|Subtotal|Tax|Tip|Receipt|Invoice|Thank you|Welcome|Thank)/i',
            '/^(Store|Location|Register|Cashier|Payment|Card|Balance|Terminal|EFTPOS)/i',
            '/^(ABN|RRN|STAN|AUTH|POS|MID|ACQ|APPROVED)/i',
            '/^(DEBIT|CREDIT|CARD|VISA|MASTERCARD|AMEX|CASH)/i',
            '/^(Change|Tendered|Amount Due|Please|For shopping|Indicates items)/i',
            '/^[=\-*#\s]+$/', // Lines with only symbols
            '/^\d{10,}$/', // Only numbers (phone, terminals)
            '/^[A-Z]{2,}:\s*\d+/', // Registry codes like "RRN: 123"
            '/^\$/', // Lines starting with just currency
            '/N\/A/', // N/A entries
            '/^APP\s*-/', // App discounts
            '/^MY\s+Card/i', // Payment methods
            '/^(CUSTOMER|THANK|GST|EFTPOS)/', // Meta lines
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return false;
            }
        }

        // Line should have some text (not just numbers and symbols)
        // And preferably have a mix of letters and numbers (item + price)
        if (preg_match('/[a-z]/i', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize line item description.
     *
     * @param string $line
     * @return string
     */
    private function sanitizeItemDescription(string $line): string
    {
        // Remove currency symbols and amounts
        $line = preg_replace('/[\$₹€£¥]?\s*([\d,]+\.?\d*)\s*$/i', '', $line);

        // Trim and limit length
        $line = trim($line);
        return substr($line, 0, 255);
    }

    /**
     * Extract total amount from receipt text.
     *
     * @param array $textBlocks Individual text blocks
     * @param string $fullText Complete text
     * @return float|null
     */
    private function extractTotalAmount(array $textBlocks, string $fullText): ?float
    {
        // Common patterns for total amounts
        $patterns = [
            '/(?:Total|Grand\s+Total|Amount\s+Due|TOTAL)\s*[\:\=]?\s*([\$₹€£¥]?)[\s]*([\d,]+\.?\d*)/i',
            '/(?:Total|TOTAL)\s+([\d,]+\.?\d*)/i',
            '/([\d,]+\.?\d*)\s*(?:Total|TOTAL)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                $amount = str_replace(',', '', $matches[count($matches) - 1]);
                return (float) $amount;
            }
        }

        return null;
    }

    /**
     * Extract vendor/store name from receipt text.
     *
     * @param array $textBlocks Individual text blocks
     * @param string $fullText Complete text
     * @return string|null
     */
    private function extractVendor(array $textBlocks, string $fullText): ?string
    {
        // Usually the vendor is at the top of the receipt
        $lines = explode("\n", $fullText);

        foreach (array_slice($lines, 0, 5) as $line) {
            $line = trim($line);

            // Skip short lines and metadata
            if (strlen($line) < 3 || strlen($line) > 100) {
                continue;
            }

            // Skip lines that look like addresses, phone numbers, or dates
            if (preg_match('/^[\d\-\(\)\s\@\#\.]+$/', $line)) {
                continue;
            }

            return substr($line, 0, 255);
        }

        return null;
    }

    /**
     * Extract date from receipt text.
     *
     * @param array $textBlocks Individual text blocks
     * @param string $fullText Complete text
     * @return string|null
     */
    private function extractDate(array $textBlocks, string $fullText): ?string
    {
        // Common date patterns
        $patterns = [
            '/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\b/', // MM/DD/YYYY or MM-DD-YY
            '/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+(\d{1,2})[,\s]+(\d{4})\b/i', // Month DD, YYYY
            '/\b(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\b/', // YYYY/MM/DD
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fullText, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Get OCR usage statistics for a group.
     *
     * @param int $groupId
     * @param int $userId
     * @return array Usage statistics
     */
    public function getUsageStats(int $groupId, int $userId): array
    {
        $cacheKey = "ocr_usage_{$groupId}_{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($groupId, $userId) {
            return [
                'monthly_used' => $this->getMonthlyUsage($groupId),
                'daily_used' => $this->getDailyUsage($groupId),
                'monthly_limit' => config('googlecloud.plans.free.monthly_ocr_scans', 5),
                'daily_limit' => config('googlecloud.plans.free.daily_ocr_scans', 2),
            ];
        });
    }

    /**
     * Get monthly OCR usage for a group.
     *
     * @param int $groupId
     * @return int Number of scans used this month
     */
    private function getMonthlyUsage(int $groupId): int
    {
        // This would query the audit log or a dedicated OCR usage table
        // For now, returning a placeholder value
        return 0;
    }

    /**
     * Get daily OCR usage for a group.
     *
     * @param int $groupId
     * @return int Number of scans used today
     */
    private function getDailyUsage(int $groupId): int
    {
        // This would query the audit log or a dedicated OCR usage table
        // For now, returning a placeholder value
        return 0;
    }
}
