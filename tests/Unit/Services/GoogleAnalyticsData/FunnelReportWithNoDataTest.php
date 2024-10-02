<?php

namespace Tests\Unit\Services\GoogleAnalyticsData;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Http;
use DDD\App\Services\GoogleAnalyticsData\GoogleAnalyticsDataService;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\FunnelStep;
use DDD\Domain\Connections\Connection;

class FunnelReportWithNoDataForStepTest extends TestCase
{
    public function test_it_returns_a_default_step()
    {
        // Arrange

        // Create metrics as associative arrays
        $metric1 = [
            'metric' => 'pageUsers',
            'pagePath' => '/home',
        ];

        // Mock the FunnelStep instance
        $step1 = Mockery::mock(FunnelStep::class)->makePartial();
        $step1->id = 'step1';
        $step1->name = 'Homepage Visits';
        $step1->metrics = collect([$metric1]);

        // Mock the Connection model
        $connection = Mockery::mock(Connection::class)->makePartial();
        $connection->uid = 'properties/123456789';
        $connection->token = [
            'access_token' => 'test_access_token'
        ];

        // Mock the Funnel class
        $funnel = Mockery::mock(Funnel::class)->makePartial();
        $funnel->steps = collect([$step1]);
        $funnel->connection = $connection;
        $funnel->conversion_value = 100;

        $startDate = '2023-01-01';
        $endDate = '2023-01-31';

        // Mock the HTTP response with no data (empty funnelTable)
        Http::fake([
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'funnelTable' => []
            ], 200),
        ]);

        // Mock the GoogleAuth facade
        Mockery::mock('alias:DDD\App\Facades\Google\GoogleAuth')
            ->shouldReceive('validateConnection')
            ->with($connection)
            ->andReturn((object)['token' => ['access_token' => 'test_access_token']]);

        // Act
        $service = new GoogleAnalyticsDataService();
        $result = $service->funnelReport($funnel, $startDate, $endDate);

        // Assert
        $this->assertNotEmpty($result->report);
        $this->assertCount(1, $result->report['steps']);
        $this->assertEquals(0, $result->report['steps'][0]['users']);
        $this->assertEquals(0, $result->report['overallConversionRate']);
        $this->assertEquals(0, $result->report['assets']);
    }
}
