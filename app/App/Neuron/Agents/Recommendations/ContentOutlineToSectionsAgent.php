<?php

namespace DDD\App\Neuron\Agents\Recommendations;

use DDD\App\Neuron\Output\ContentOutlineSections;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class ContentOutlineToSectionsAgent extends Agent
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
        return 'I am going to give you a Content Outline. Your job is to convert it into a JSON array. '
            . 'The Content Outline contains Sections, labeled Section 1, Section 2, Section 3, etc. '
            . 'Your JSON array must contain an object for each Section with an "outline" property containing the section content.';
    }

    protected function getOutputClass(): string
    {
        return ContentOutlineSections::class;
    }
}
