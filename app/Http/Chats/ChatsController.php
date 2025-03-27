<?php

namespace DDD\Http\Chats;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\App\Services\OpenAi\GPTService;
use DDD\App\Controllers\Controller;

class ChatsController extends Controller
{
    protected $gptService;

    public function __construct(GPTService $gptService)
    {
        $this->gptService = $gptService;
    }
    
    public function store(Organization $organization, Request $request)
  {
      set_time_limit(300);

      // Validate the incoming request
      $request->validate([
          'message' => 'required|string',
          'prototype_html' => 'nullable|string',
          'attached_elements' => 'nullable|array',
          'attached_elements.*' => 'string',
          'response_format' => 'nullable|string',
      ]);

      // Base instructions for Grok
      $instructions = "You are a coding expert. " . 
      "I am requesting changes to an HTML prototype." . 
      // "I may include attached elements I want you to update in the prototype html." . 
      "You must return the whole prototype html with your changes.";
      // "Please provide your response in JSON format with two keys:\n" . 
      // "1. 'message' (a string with your natural language response)\n" . 
      // "2. 'data' (the structured data as JSON with the structure { code: the entire updated prototype code. })\n\n" . 
      // "Do not wrap the response in Markdown code blocks (e.g., ```json). Return only the raw JSON.\n\n";

      // Get the individual components from the request
      $userMessage = $request->input('message');
      $prototypeHtml = $request->input('prototype_html');
      $attachedElements = $request->input('attached_elements', []);
      $responseFormat = $request->input('response_format');

      // Start with users message
      $fullMessage = "User request:\n" . $userMessage;

      // Add the attached elements
      if (!empty($attachedElements)) {
        $fullMessage .= "\n\n Elements to be changed in the prototype html:\n";
        $fullMessage .= implode("\n", $attachedElements);
      }

      // Include the prototype HTML
      $fullMessage .= "\n\nPrototype HTML:\n" . $prototypeHtml;

      // log the instructions
      // Log::info("Instructions: \n" . $instructions . "\n\n");
      // Log::info("Full Message:  \n" . $fullMessage . "\n\n");
      // Log::info("Response format: \n" . $responseFormat . "\n\n");

      // Send the formatted message to Grok
      $response = $this->gptService->chat($instructions, $fullMessage, $responseFormat);

      // Attempt to decode the response as JSON if a format was requested
      $structuredResponse = $responseFormat ? json_decode($response, true) : $response;

      // If decoding fails, return the raw response as a fallback
      if ($responseFormat && json_last_error() !== JSON_ERROR_NONE) {
          $structuredResponse = ['error' => 'Invalid response format ', 'raw_response' => $response];
      }

      // Return the structured response directly
      return response()->json($structuredResponse);
  }
}