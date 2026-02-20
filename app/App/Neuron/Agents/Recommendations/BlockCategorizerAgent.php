<?php

namespace DDD\App\Neuron\Agents\Recommendations;

use DDD\App\Neuron\Output\BlockHtmlOutput;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class BlockCategorizerAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: config('services.openai.api_key'),
            model: 'gpt-4o-2024-11-20',
            parameters: [],
            strict_response: false,
            httpOptions: new HttpClientOptions(timeout: 300),
        );
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
You are an expert web developer.
Convert the following React code (which may contain multiple components) to a single cohesive vanilla HTML section with Tailwind CSS.
Combine all components into one logical section, maintaining their relationships (e.g., if one component is imported into another).
If there are interactive components, include the necessary vanilla JavaScript.
Use placeholder images from placehold.co (e.g. https://placehold.co/600x400) where images exist.
Use FontAwesome where icons.
Wrap the result in a <section> tag.
Return only the component HTML code as a string, nothing else before or after.

Finally, evaluate the structure and content of the component and assign a category. Valid categories: hero, feature-list, single-feature, rate-highlight, rate-table, other-table, calculator, how-it-works, testimonials, faqs, blogs, resources, cta, contact-info, text-single-column, text-multi-column, stats, logos, comparison-table, team, disclosures, other.
PROMPT;
    }

    protected function getOutputClass(): string
    {
        return BlockHtmlOutput::class;
    }
}