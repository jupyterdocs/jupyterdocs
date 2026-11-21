<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServerTimingTest extends TestCase
{
    public function test_responses_report_where_the_time_went(): void
    {
        $header = $this->get('/')->assertOk()->headers->get('Server-Timing');

        $this->assertMatchesRegularExpression('/app;dur=[\d.]+/', $header);
        $this->assertMatchesRegularExpression('/db;dur=[\d.]+;desc="\d+ queries"/', $header);
    }
}
