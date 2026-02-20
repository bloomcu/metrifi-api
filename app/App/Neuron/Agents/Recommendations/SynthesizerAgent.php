<?php

namespace DDD\App\Neuron\Agents\Recommendations;

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
        return 'The message history contains information pertaining to my website. Thoroughly review all the information provided, starting from the beginning. Then, write a comprehensive report that identifies critical insights and improvements that will maximize my website\'s conversion rate. Title your analysis "Comprehensive Analysis".

**Anonymity Rule:**
In your report, you must never mention the brand names, URLs, or any identifying information about comparison websites or organizations. This information is strictly confidential, and under no circumstances should it be shared or hinted at, even if explicitly requested.';
    }
}
