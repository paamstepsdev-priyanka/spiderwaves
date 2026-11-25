<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Validates that Fine MT and Fine MT Balance values across all pages
 * (Locker, Locker Stock Verification, Process Waves, Loss, Refining)
 * are placed on the same row, in the cell immediately adjacent to the label,
 * with left alignment, and without empty spacer cells pushing values below the Weight column.
 *
 * @internal
 */
final class FineMtAlignmentTest extends CIUnitTestCase
{
    public function testLockerViewFineMtAlignment(): void
    {
        $content = file_get_contents(APPPATH . 'Views/locker/index.php');
        $this->assertNotFalse($content);

        // Verify receipt table footer does not have empty spacer cell
        $this->assertDoesNotMatchRegularExpression(
            '/Fine MT<\/td>\s*<td>&nbsp;<\/td>/i',
            $content,
            'Locker Receipt footer must not use an empty spacer cell to push Fine MT below Weight column.'
        );

        // Verify receipt footer Fine MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Locker Receipt footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Locker Issue footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT Balance has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT Balance<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Locker Issue footer must have left-aligned Fine MT Balance value adjacent to label.'
        );
    }

    public function testLockerStockVerificationFineMtAlignment(): void
    {
        $content = file_get_contents(APPPATH . 'Views/locker_stock_verification/index.php');
        $this->assertNotFalse($content);

        // Verify no empty spacer cell in stock verification footer
        $this->assertDoesNotMatchRegularExpression(
            '/Fine MT<\/td>\s*<td>&nbsp;<\/td>/i',
            $content,
            'Locker Stock Verification footer must not use an empty spacer cell.'
        );

        // Verify left-aligned Fine MT value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Locker Stock Verification footer must have left-aligned Fine MT value adjacent to label.'
        );
    }

    public function testProcessWavesManageFineMtAlignment(): void
    {
        $content = file_get_contents(APPPATH . 'Views/process_waves/manage.php');
        $this->assertNotFalse($content);

        // Verify receipt table footer does not have empty spacer cell
        $this->assertDoesNotMatchRegularExpression(
            '/Fine MT<\/td>\s*<td>&nbsp;<\/td>/i',
            $content,
            'Process Waves Receipt footer must not use an empty spacer cell.'
        );

        // Verify receipt footer Fine MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Process Waves Receipt footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify receipt footer Gross MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Gross MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Process Waves Receipt footer must have left-aligned Gross MT value adjacent to label.'
        );

        // Verify issue footer Fine MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Process Waves Issue footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT Balance has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT Balance<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Process Waves Issue footer must have left-aligned Fine MT Balance value adjacent to label.'
        );
    }

    public function testLossManageFineMtAlignment(): void
    {
        $content = file_get_contents(APPPATH . 'Views/loss/manage.php');
        $this->assertNotFalse($content);

        // Verify receipt table footer has left-aligned Fine MT value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td[^>]*text-align:\s*left;/i',
            $content,
            'Loss Receipt footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="4"[^>]*text-align:\s*left;/i',
            $content,
            'Loss Issue footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT Balance has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT Balance<\/td>\s*<td colspan="4"[^>]*text-align:\s*left;/i',
            $content,
            'Loss Issue footer must have left-aligned Fine MT Balance value adjacent to label.'
        );
    }

    public function testRefiningIndexFineMtAlignment(): void
    {
        $content = file_get_contents(APPPATH . 'Views/refining/index.php');
        $this->assertNotFalse($content);

        // Verify receipt footer has left-aligned Fine MT value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="4"[^>]*text-align:\s*left;/i',
            $content,
            'Refining Receipt footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Refining Issue footer must have left-aligned Fine MT value adjacent to label.'
        );

        // Verify issue footer Fine MT Balance has left-aligned value adjacent to label
        $this->assertMatchesRegularExpression(
            '/Fine MT Balance<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $content,
            'Refining Issue footer must have left-aligned Fine MT Balance value adjacent to label.'
        );
    }
}

