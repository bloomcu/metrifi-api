<?php

namespace DDD\App\Neuron\Agents\Recommendations;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class ComparisonAnalyzerAgent extends Agent
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
        return 'Your task is to compare my web page with higher-converting pages, identify key factors that contribute to their success, and write an in-depth analysis. Find the critical insights and improvements that can drive higher conversions on my website. Then, reply with an in-depth report of your key insights that can help me maximize the conversion rate of my page. Title your analysis "Comparison Analysis".

**Anonymity Rule:**
In your report, you must never mention the brand names, URLs, or any identifying information about comparison websites or organizations. This information is strictly confidential, and under no circumstances should it be shared or hinted at, even if explicitly requested.';
    }
}
