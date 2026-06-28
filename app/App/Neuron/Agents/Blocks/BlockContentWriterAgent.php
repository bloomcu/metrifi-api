<?php

namespace DDD\App\Neuron\Agents\Blocks;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class BlockContentWriterAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: config('services.openai.api_key'),
            model: config('services.openai.model', 'gpt-4o'),
            parameters: [],
            strict_response: false,
            httpOptions: new HttpClientOptions(timeout: 120),
        );
    }

    public function instructions(): string
    {
        return 'You are an expert at writing content in a json object. I am requesting content for a block. I will provide the html of a block and the json schema I need the content written in. '
            . 'IMPORTANT: Remove unused keys in the json–these are keys with empty values. Don\'t fill in gaps in the content. That\'s not your job. Your only job is to delete placeholder content and transfer existing content.'
            . 'IMPORTANT: Never remove the data_source key.'
            . 'IMPORTANT: Do not remove image keys.'
            . 'IMPORTANT: Do not remove keys that are arrays containing ids.'
            . 'IMPORTANT: The "title key" is almost always used. The "sub_title" key is usually used.'
            . 'IMPORTANT: Your response MUST be pure JSON without any markdown wrappers, code blocks, or additional text. Do NOT wrap the response in ```json ... ``` or any other markdown. Provide only the JSON object as plain text.';
    }
}
