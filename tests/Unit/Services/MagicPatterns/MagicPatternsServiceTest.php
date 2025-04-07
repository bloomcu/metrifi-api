<?php

namespace Tests\Unit\Services\MagicPatterns;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use DDD\App\Services\MagicPatterns\MagicPatternsService;

class MagicPatternsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up config for testing
        Config::set('services.magicpatterns.api_key', 'test_api_key');
    }

    public function test_it_creates_design_successfully()
    {
        // Arrange
        $prompt = 'Create a landing page with a hero section';
        
        // Mock the HTTP response
        Http::fake([
            'https://api.magicpatterns.com/api/v2/pattern' => Http::response([
                'id' => 'design123',
                'editorUrl' => 'https://magicpatterns.com/editor/design123',
                'previewUrl' => 'https://magicpatterns.com/preview/design123',
                'sourceFiles' => [
                    [
                        'id' => 'file1',
                        'name' => 'components/Hero.tsx',
                        'type' => 'javascript',
                        'isReadOnly' => false,
                        'code' => '<div>Hero Component</div>'
                    ],
                    [
                        'id' => 'file2',
                        'name' => 'components/Button.tsx',
                        'type' => 'javascript',
                        'isReadOnly' => false,
                        'code' => '<button>Click Me</button>'
                    ]
                ]
            ], 200)
        ]);

        // Act - use minimal timeout and retry settings for tests
        $service = new MagicPatternsService();
        $result = $service->createDesign($prompt, 1, 1, 1);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals('design123', $result['id']);
        $this->assertEquals('https://magicpatterns.com/editor/design123', $result['editorUrl']);
        $this->assertEquals('https://magicpatterns.com/preview/design123', $result['previewUrl']);
        $this->assertCount(2, $result['components']);
        $this->assertEquals('components/Hero.tsx', $result['components'][0]['name']);
        $this->assertEquals('components/Button.tsx', $result['components'][1]['name']);
    }

    public function test_it_throws_exception_when_no_component_files_found()
    {
        // Arrange
        $prompt = 'Create a landing page with a hero section';
        
        // Mock the HTTP response with no component files
        Http::fake([
            'https://api.magicpatterns.com/api/v2/pattern' => Http::response([
                'id' => 'design123',
                'editorUrl' => 'https://magicpatterns.com/editor/design123',
                'previewUrl' => 'https://magicpatterns.com/preview/design123',
                'sourceFiles' => [
                    [
                        'id' => 'file1',
                        'name' => 'styles/global.css',
                        'type' => 'css',
                        'isReadOnly' => false,
                        'code' => 'body { margin: 0; }'
                    ]
                ]
            ], 200)
        ]);

        // Mock Log facade to verify it's being called
        Log::shouldReceive('error')->once()->withAnyArgs();
        
        // Act & Assert - use minimal timeout and retry settings for tests
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Magic Patterns: No usable code found in Magic Patterns response components or app.tsx/');
        
        $service = new MagicPatternsService();
        $service->createDesign($prompt, 1, 1, 1);
    }

    public function test_it_falls_back_to_app_tsx_when_no_components_directory()
    {
        // Arrange
        $prompt = 'Create a landing page with a hero section';
        
        // Mock the HTTP response with App.tsx but no components directory
        Http::fake([
            'https://api.magicpatterns.com/api/v2/pattern' => Http::response([
                'id' => 'design123',
                'editorUrl' => 'https://magicpatterns.com/editor/design123',
                'previewUrl' => 'https://magicpatterns.com/preview/design123',
                'sourceFiles' => [
                    [
                        'id' => 'file1',
                        'name' => 'App.tsx',
                        'type' => 'javascript',
                        'isReadOnly' => false,
                        'code' => 'function App() { return <div>App Component</div>; }'
                    ]
                ]
            ], 200)
        ]);

        // Act - use minimal timeout and retry settings for tests
        $service = new MagicPatternsService();
        $result = $service->createDesign($prompt, 1, 1, 1);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertEquals('design123', $result['id']);
        $this->assertCount(1, $result['components']);
        $this->assertEquals('App.tsx', $result['components'][0]['name']);
    }

    public function test_it_throws_exception_when_api_request_fails()
    {
        // Arrange
        $prompt = 'Create a landing page with a hero section';
        
        // Mock the HTTP response with a failure
        Http::fake([
            'https://api.magicpatterns.com/api/v2/pattern' => Http::response([
                'error' => 'Invalid API key'
            ], 401)
        ]);
        
        // Act & Assert - use minimal timeout and retry settings for tests
        $this->expectException(\Exception::class);
        
        $service = new MagicPatternsService();
        $service->createDesign($prompt, 1, 1, 1);
    }
}
