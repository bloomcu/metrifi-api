<?php

namespace DDD\App\Neuron\Agents\Recommendations;

use DDD\App\Neuron\RecommendationPrompts;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class SynthesizerAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: config('services.openai.api_key'),
            model: config('services.openai.model', 'gpt-4o'),
            parameters: [],
            strict_response: false,
            httpOptions: new HttpClientOptions(timeout: 300),
        );
    }

    public function instructions(): string
    {
        return RecommendationPrompts::get('synthesizer');
    }
}
