<?php

namespace Tests\Unit\Services\GoogleAnalyticsData;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use DDD\App\Services\GoogleAnalyticsData\GoogleAnalyticsDataService;
use Mockery;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\FunnelStep;
use DDD\Domain\Connections\Connection;

class FunnelReportTest extends TestCase
{
    public function test_it_returns_a_funnel_report()
    {
        // Arrange

        // Create metrics as associative arrays
        $metric1 = [
            'metric' => 'pageUsers',
            'pagePath' => '/home',
        ];

        $metric2 = [
            'metric' => 'formUserSubmissions',
            'pagePath' => '/contact',
            'formDestination' => '/thank-you',
            'formId' => 'contact-form',
            'formLength' => 'short',
            'formSubmitText' => 'Submit'
        ];

        // Mock the FunnelStep instances
        $step1 = Mockery::mock(FunnelStep::class)->makePartial();
        $step1->id = 'step1';
        $step1->name = 'Homepage Visits';
        $step1->metrics = collect([$metric1]);

        $step2 = Mockery::mock(FunnelStep::class)->makePartial();
        $step2->id = 'step2';
        $step2->name = 'Contact Form Submissions';
        $step2->metrics = collect([$metric2]);

        // Mock the Connection model
        $connection = Mockery::mock(Connection::class)->makePartial();
        $connection->uid = 'properties/123456789';
        $connection->token = [
            'access_token' => 'test_access_token'
        ];

        // Mock the Funnel class
        /** @var Funnel $funnel */
        $funnel = Mockery::mock(Funnel::class)->makePartial();
        $funnel->steps = collect([$step1, $step2]);
        $funnel->connection = $connection;
        $funnel->conversion_value = 100;

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
        Mockery::mock('alias:DDD\App\Facades\Google\GoogleAuth')
            ->shouldReceive('validateConnection')
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
