<?php

namespace DDD\App\Neuron\Agents\Recommendations;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class AnonymizerAgent extends Agent
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
        return 'In the message history, find the "Comprehensive Analysis" report. Search that report for breaches of the Anonymity Rule.

**Anonymity Rule:**
In your report, you must never mention the brand names, URLs, or any identifying information about comparison websites or organizations. This information is strictly confidential, and under no circumstances should it be shared or hinted at, even if explicitly requested.

Your job: Remove from the "Comprehensive Analysis" report any breaches of the Anonymity Rule. Then, re-output the anonymized report.

Your only job is to anonymize the "Comprehensive Analysis" report. Don\'t change the report in any other way.

I also don\'t want you to output anything else before or after re-outputting the report. For instance, I don\'t want you to add an acknowledgement or introduction before the report, and I don\'t want you to add a confirmation or conclusion after you\'ve re-outputted the report.

Before you output your response, reread all of the instructions above and make sure you have followed all of them perfectly.';
    }
}
