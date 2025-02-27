<?php

namespace DDD\Http\Chats;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\App\Services\Grok\GrokService;
use DDD\App\Controllers\Controller;

class ChatsController extends Controller
{
  protected $grokService;

    public function __construct(GrokService $grokService)
    {
        $this->grokService = $grokService;
    }
    
    public function store(Organization $organization, Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'message' => 'required|string',
            'response_format' => 'nullable|string', // Optional response format
        ]);

        // Base instructions for Grok
        $instructions = "You are a helpful assistant.";

        // Get the message and optional response format from the request
        $message = $request->input('message');
        $responseFormat = $request->input('response_format');

        // Send the message to Grok and get the response
        $response = $this->grokService->chat($instructions, $message, $responseFormat);

        // Attempt to decode the response as JSON if a format was requested
        $structuredResponse = $responseFormat ? json_decode($response, true) : $response;

        // If decoding fails, return the raw response as a fallback
        if ($responseFormat && json_last_error() !== JSON_ERROR_NONE) {
            $structuredResponse = ['error' => 'Invalid response format from Grok', 'raw_response' => $response];
        }

        return response()->json([
            'data' => $structuredResponse
        ]);
    }
}