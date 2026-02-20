<?php

namespace DDD\App\Neuron\Agents\Recommendations;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class WriteContentOutlineAgent extends Agent
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
        return <<<'PROMPT'
I want you to create one extremely detailed content outline that will help me maximize website conversions. Title it "Content Outline".

With your content outline in hand, I should have absolutely everything I need for the content of my web page, so all I have to do is copy and paste the content from your outline.

You have information about my current web page and comparison web pages, as well as a "Comprehensive Analysis". Use those resources to outline every type of content and component that should exist on a maximum-converting version of my web page. In your outline, clearly delineate each section of content by numbering and labeling it at the top. Examples:

"""
Section 1: Hero

Section 2: Key Benefits

Section 3: Interactive Loan Calculator

And so on …
"""

Write high-quality text content for each section of the improved web page:

- Do not plagiarize any content from comparison web pages. You should certainly learn from the comparisons and you can write something similar if that's the best way to optimize for conversion. Just don't copy any of the comparisons' text word for word; change it enough so it's not plagiarism.
- Evaluate all the content on my current page as well as all the content on the comparisons I've given you.
- Use any proven insights and patterns you already know about how to create high-converting web pages.
- Don't skimp on the content. Make it thorough and extremely useful for end-users.
- Remember to write text for call-to-action buttons. The first and last sections of the page should always have call-to-action buttons.
- Write all content in sentence case.
- Don't use exclamation marks.
- Don't write cheesy-sounding content.
- If you don't have the content you need for a section (such as testimonials), make up example content and put it in brackets ([]) to indicate that I need to replace it with real content. Your placeholder content should be a perfect example of the type of content that should go there.
- Do not say anything about the website's top navigation (aka header) or footer.
- Your job is to provide page content. So, do not say anything about general page optimization that doesn't have to do with content, like mobile responsiveness or page speed.
- Don't write an introduction before the content outline or a conclusion after the content outline. Just write the content outline.

Deliverable: One extremely detailed "Content Outline" with clear sections and high-quality content that will maximize my conversions.
PROMPT;
    }
}
