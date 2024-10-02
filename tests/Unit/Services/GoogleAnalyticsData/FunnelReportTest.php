<?php

namespace Tests\Unit\Services\GoogleAnalyticsData;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use DDD\App\Services\GoogleAnalyticsData\GoogleAnalyticsDataService;
use DDD\Domain\Funnels\Data\FunnelData;
use DDD\Domain\Funnels\Data\StepData;
use DDD\Domain\Funnels\Data\MetricData;
use DDD\Domain\Connections\Data\ConnectionData;
use Mockery;

class FunnelReportTest extends TestCase
{
    public function test_it_returns_a_funnel_report()
    {
        // Create metrics with named arguments
        $metric1 = new MetricData(
            metric: 'pageUsers',
            attributes: [
                'pagePath' => '/home'
            ]
        );

        $metric2 = new MetricData(
            metric: 'formUserSubmissions',
            attributes: [
                'pagePath' => '/contact',
                'formDestination' => '/thank-you',
                'formId' => 'contact-form',
                'formLength' => 'short',
                'formSubmitText' => 'Submit'
            ]
        );

        // Create steps with named arguments
        $step1 = new StepData(
            id: 'step1',
            name: 'Homepage Visits',
            metrics: collect([$metric1])
        );

        $step2 = new StepData(
            id: 'step2',
            name: 'Contact Form Submissions',
            metrics: collect([$metric2])
        );

        // Create a connection object with named arguments
        $connection = new ConnectionData(
            uid: 'properties/123456789',
            token: [
                'access_token' => 'test_access_token'
            ]
        );

        // Create the Funnel object with named arguments
        $funnel = new FunnelData(
            steps: collect([$step1, $step2]),
            connection: $connection,
            conversion_value: 100
        );

        $startDate = '2023-01-01';
        $endDate = '2023-01-31';
        $disabledSteps = [];

        // Mock the HTTP response
        Http::fake([
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'funnelTable' => [
                    'rows' => [
                        [
                            'dimensionValues' => [
                                ['value' => 'Homepage Visits'],
                            ],
                            'metricValues' => [
                                ['value' => '1000'],
                            ],
                        ],
                        [
                            'dimensionValues' => [
                                ['value' => 'Contact Form Submissions'],
                            ],
                            'metricValues' => [
                                ['value' => '100'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Mock the GoogleAuth facade
        $googleAuthMock = Mockery::mock('alias:DDD\App\Facades\Google\GoogleAuth');
        $googleAuthMock->shouldReceive('validateConnection')
            ->with($connection)
            ->andReturn((object)['token' => ['access_token' => 'test_access_token']]);

        // Act
        $service = new GoogleAnalyticsDataService();
        $result = $service->funnelReport($funnel, $startDate, $endDate, $disabledSteps);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals(1000, $result->report['steps'][0]['users']);
        $this->assertEquals(100, $result->report['steps'][1]['users']);
        $this->assertEquals(10.0, $result->report['steps'][1]['conversionRate']); // Assuming conversion rate calculated correctly
        $this->assertEquals(10.0, $result->report['overallConversionRate']); // Assuming overall conversion rate calculated correctly
    }
}
