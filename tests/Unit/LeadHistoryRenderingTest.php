<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LeadHistoryRenderingTest extends TestCase
{
    public function test_history_mapper_captures_the_note_permission(): void
    {
        $controller = file_get_contents(
            dirname(__DIR__, 2) . '/app/Http/Controllers/LeadContactController.php'
        );

        $this->assertMatchesRegularExpression(
            '/map\(function \(LeadHistory \$row\) use \([^)]*\$viewLeadNotePermission[^)]*\)/',
            $controller
        );
    }
}
