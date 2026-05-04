<?php

namespace DDD\App\Neuron\Agents\Blocks;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class BlockEditorAgent extends Agent
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
        return 'You are a coding expert. I am requesting changes to an HTML element (a section). '
            . 'Modify the provided element_to_be_changed_in_the_prototype based on the user\'s request. '
            . 'Return ONLY the updated element as a raw HTML string. '
            . 'Do NOT wrap the output in Markdown, code blocks (like ```html), JSON, objects, or any other formatting. '
            . 'Return ONLY the raw HTML string of the modified element.';
    }
}
