<?php

namespace Tests\Unit\Services\GoogleAnalyticsData;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Http;
use DDD\Domain\Connections\Data\ConnectionData;
use DDD\App\Services\GoogleAnalyticsData\GoogleAnalyticsDataService;

class PageUsersTest extends TestCase
{
    public function test_it_returns_a_page_user_report()
    {
        // Arrange
        $connection = new ConnectionData(
            uid: 'properties/123456789',
            token: [
                'access_token' => 'test_access_token'
                ]
        );
        $startDate = '2023-01-01';
        $endDate = '2023-01-31';
        $exact = ['/home', '/contact'];

        // Mock the HTTP response
        Http::fake([
            'https://analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '/home'],
                            ['value' => 'example.com'],
                        ],
                        'metricValues' => [
                            ['value' => '500'],
                        ],
                    ],
                    [
                        'dimensionValues' => [
                            ['value' => '/contact'],
                            ['value' => 'example.com'],
                        ],
                        'metricValues' => [
                            ['value' => '300'],
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
        $result = $service->pageUsers($connection, $startDate, $endDate, $exact);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals('500', $result['rows'][0]['metricValues'][0]['value']);
        $this->assertEquals('/home', $result['rows'][0]['dimensionValues'][0]['value']);
    }
}
