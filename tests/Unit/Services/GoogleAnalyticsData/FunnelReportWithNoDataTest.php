<?php

namespace Tests\Unit\Services\GoogleAnalyticsData;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Http;
use DDD\Domain\Funnels\Data\StepData;
use DDD\Domain\Funnels\Data\MetricData;
use DDD\Domain\Funnels\Data\FunnelData;
use DDD\Domain\Connections\Data\ConnectionData;
use DDD\App\Services\GoogleAnalyticsData\GoogleAnalyticsDataService;

class FunnelReportWithNoDataForStepTest extends TestCase
{
    public function test_it_returns_a_default_step()
    {
        // Arrange
        $metric1 = new MetricData(
            metric: 'pageUsers',
            attributes: [
                'pagePath' => '/home'
            ]
        );

        $step1 = new StepData(
            id: 'step1',
            name: 'Homepage Visits',
            metrics: collect([$metric1])
        );

        $connection = new ConnectionData(
            uid: 'properties/123456789',
            token: [
                'access_token' => 'test_access_token'
            ]
        );

        $funnel = new FunnelData(
            steps: collect([$step1]),
            connection: $connection,
            conversion_value: 100
        );

        $startDate = '2023-01-01';
        $endDate = '2023-01-31';

        // Mock the HTTP response with no data (empty funnelTable)
        Http::fake([
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'funnelTable' => []
            ], 200),
        ]);

        // Mock the GoogleAuth facade
        $googleAuthMock = Mockery::mock('alias:DDD\App\Facades\Google\GoogleAuth');
        $googleAuthMock->shouldReceive('validateConnection')
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
