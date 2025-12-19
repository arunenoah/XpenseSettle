<?php

namespace Tests\Unit;

use App\Services\OcrService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class OcrServiceTest extends TestCase
{
    private OcrService $ocrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ocrService = new OcrService();
    }

    /**
     * Test that OCR service initialization respects enabled flag.
     *
     * @test
     */
    public function testOcrServiceInitialization()
    {
        // Check if service is enabled (will be false without proper config)
        $isEnabled = $this->ocrService->isEnabled();
        $this->assertIsBool($isEnabled);
    }

    /**
     * Test parsing text to extract line items.
     *
     * @test
     */
    public function testExtractLineItems()
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('extractLineItems');
        $method->setAccessible(true);

        $textBlocks = [
            'Item 1 - $5.99',
            'Item 2 - $10.50',
            'Tax - $1.25',
        ];

        $fullText = "Item 1 - $5.99\nItem 2 - $10.50\nTax - $1.25";

        $items = $method->invoke($this->ocrService, $textBlocks, $fullText);

        // Should have extracted items
        $this->assertIsArray($items);
        $this->assertGreaterThan(0, count($items));

        // Each item should have description and amount
        foreach ($items as $item) {
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('amount', $item);
        }
    }

    /**
     * Test extracting total amount from receipt text.
     *
     * @test
     */
    public function testExtractTotalAmount()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('extractTotalAmount');
        $method->setAccessible(true);

        $testCases = [
            "Item 1\nItem 2\nTotal: $25.99" => 25.99,
            "Item 1\nItem 2\nGRAND TOTAL: $100.50" => 100.50,
            "Item 1\nItem 2\n$75.25 Total" => 75.25,
            "No total here" => null,
        ];

        foreach ($testCases as $text => $expected) {
            $result = $method->invoke($this->ocrService, [], $text);
            $this->assertEquals($expected, $result, "Failed for text: $text");
        }
    }

    /**
     * Test extracting vendor name from receipt text.
     *
     * @test
     */
    public function testExtractVendor()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('extractVendor');
        $method->setAccessible(true);

        $fullText = "Whole Foods Market\n123 Main St\nDate: 10/15/2024\nItem 1: $5.99";
        $result = $method->invoke($this->ocrService, [], $fullText);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Whole Foods', $result);
    }

    /**
     * Test extracting date from receipt text.
     *
     * @test
     */
    public function testExtractDate()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('extractDate');
        $method->setAccessible(true);

        $testCases = [
            "Date: 10/15/2024" => '10/15/2024',
            "Date: 2024-10-15" => '2024-10-15',
            "No date here" => null,
        ];

        foreach ($testCases as $text => $expected) {
            $result = $method->invoke($this->ocrService, [], $text);
            if ($expected === null) {
                $this->assertNull($result);
            } else {
                $this->assertNotNull($result);
            }
        }
    }

    /**
     * Test text parsing for expense data.
     *
     * @test
     */
    public function testParseTextToExpenseData()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('parseTextToExpenseData');
        $method->setAccessible(true);

        // Create mock text annotations
        $mockAnnotations = [];

        // If we have annotations, test should work
        if (empty($mockAnnotations)) {
            // Test with empty annotations
            $result = $method->invoke($this->ocrService, $mockAnnotations);

            $this->assertArrayHasKey('raw_text', $result);
            $this->assertArrayHasKey('items', $result);
            $this->assertArrayHasKey('total_amount', $result);
            $this->assertArrayHasKey('vendor', $result);
            $this->assertArrayHasKey('confidence', $result);
            $this->assertArrayHasKey('parse_status', $result);
        }
    }

    /**
     * Test file validation.
     *
     * @test
     */
    public function testFileValidation()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('validateImage');
        $method->setAccessible(true);

        // Test with a mock file
        $file = new UploadedFile(
            __FILE__,
            'test.php',
            'application/octet-stream',
            null,
            true
        );

        // Should throw exception for invalid file type
        $this->expectException(\Exception::class);
        $method->invoke($this->ocrService, $file);
    }

    /**
     * Test sanitizing item descriptions.
     *
     * @test
     */
    public function testSanitizeItemDescription()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('sanitizeItemDescription');
        $method->setAccessible(true);

        $testCases = [
            'Item Name - $5.99' => 'Item Name',
            'Product 123 £10.50' => 'Product 123',
            'Description with ₹100' => 'Description with',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($this->ocrService, $input);
            $this->assertStringStartsWith($expected, $result);
        }
    }

    /**
     * Test line item likelihood detection.
     *
     * @test
     */
    public function testIsLikelyLineItem()
    {
        $reflection = new \ReflectionClass($this->ocrService);
        $method = $reflection->getMethod('isLikelyLineItem');
        $method->setAccessible(true);

        $likelyItems = [
            'Milk 2% - $3.99',
            'Bread - $2.50',
            'Cheese - $5.00',
        ];

        $unlikelyItems = [
            'Date',
            'Total',
            'Thank you',
            '===',
            '5551234567',
        ];

        foreach ($likelyItems as $item) {
            $this->assertTrue($method->invoke($this->ocrService, $item), "Should be likely: $item");
        }

        foreach ($unlikelyItems as $item) {
            $this->assertFalse($method->invoke($this->ocrService, $item), "Should not be likely: $item");
        }
    }

    /**
     * Test getting usage statistics.
     *
     * @test
     */
    public function testGetUsageStats()
    {
        $stats = $this->ocrService->getUsageStats(1, 1);

        $this->assertArrayHasKey('monthly_used', $stats);
        $this->assertArrayHasKey('daily_used', $stats);
        $this->assertArrayHasKey('monthly_limit', $stats);
        $this->assertArrayHasKey('daily_limit', $stats);

        $this->assertIsInt($stats['monthly_used']);
        $this->assertIsInt($stats['daily_used']);
        $this->assertIsInt($stats['monthly_limit']);
        $this->assertIsInt($stats['daily_limit']);
    }
}
