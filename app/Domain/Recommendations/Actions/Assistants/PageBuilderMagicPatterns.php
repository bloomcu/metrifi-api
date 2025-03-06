<?php

namespace DDD\Domain\Recommendations\Actions\Assistants;

use DDD\App\Services\MagicPatterns\MagicPatternsService;
use DDD\App\Services\Grok\GrokService;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use DDD\Domain\Recommendations\Recommendation;
use DDD\App\Services\OpenAI\AssistantService;

class PageBuilderMagicPatterns implements ShouldQueue
{
    use AsAction, InteractsWithQueue, Queueable, SerializesModels;
    
    public $name = 'page_builder';
    public $timeout = 300;

    protected AssistantService $assistant;
    protected MagicPatternsService $magicPatterns;
    protected GrokService $grok;

    public function __construct(
        AssistantService $assistant,
        MagicPatternsService $magicPatterns,
        GrokService $grok
    ) {
        $this->assistant = $assistant;
        $this->magicPatterns = $magicPatterns;
        $this->grok = $grok;
    }

    public function handle(Recommendation $recommendation)
    {
        $recommendation->update(['status' => $this->name . '_in_progress']);

        // Build the prompt for Magic Patterns
        $prompt = "Build section " . ($recommendation->sections_built + 1) . 
                 " from the Content Outline: " . $recommendation->content_outline;

        try {
            // Get design from Magic Patterns
            $magicResponse = $this->magicPatterns->createDesign(
                prompt: $prompt,
            );

            Log::info('Magic Patterns response: ' . json_encode($magicResponse));

            // Extract the components from the response
            $components = $magicResponse['components'] ?? [];
            
            if (empty($components)) {
                Log::info('No components found in Magic Patterns response');
                $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: No components found in Magic Patterns response</section>';
            } else {
                // Combine all component code into a single string
                $combinedCode = '';
                foreach ($components as $component) {
                    $generatedCode = $component['code'] ?? '';
                    if (!empty($generatedCode)) {
                        $combinedCode .= "\n\n// Component: " . ($component['name'] ?? 'unnamed') . "\n" . $generatedCode;
                    }
                }

                if (empty($combinedCode)) {
                    Log::info('No valid code found in components');
                    $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: No valid code found in components</section>';
                } else {
                    try {
                        // Convert all components at once to vanilla HTML/CSS using Grok
                        $htmlCss = $this->grok->chat(
                            instructions: '
                            You are an expert web developer.
                            Convert the following React code (which may contain multiple components) to a single cohesive vanilla HTML section with Tailwind CSS.
                            Combine all components into one logical section, maintaining their relationships (e.g., if one component is imported into another).
                            If there are interactive components, include the necessary vanilla JavaScript.
                            Use placeholder images from placehold.co (e.g. https://placehold.co/600x400) where images exist.
                            Use FontAwesome where icons exist.
                            Return only the component HTML code as a string, nothing else before or after.
                            Wrap the result in a <section> tag.
                            Add an id attribute to the section tag with a unique id attribute using timestamp.
                            Add a custom attribute called "block-category".
                            I am going to give you an HTML webpage component. Your job is to evaluate the structure and content of the component and then assign a value to its attribute called "block-category". Below, I give details that will help you identify the correct block category.

                            block-category="hero":
                            If the component appears to be a "hero" section, or introduction to a webpage, then assign the value "hero". Hero sections come in many forms, but the following elements are common:
                            They always have an <h1>, usually with large text.
                            They usually have a subheading.
                            They always have a call to action button.
                            They sometimes have one or more images.

                            block-category="feature-list":
                            If the component appears to be a list of features, benefits, or value propositions, then assign the value "feature-list". Feature lists come in many forms, but the following elements are common:
                            They always include at least two items.
                            They often include icons.
                            The text content is usually about features, benefits, or value propositions related to a product or service.

                            block-category="single-feature":
                            If the component appears to highlight a single feature, benefit, or value proposition, then assign the value "single-feature". Single-feature sections come in many forms, but the following elements are common:
                            They always highlight a particular feature, benefit, or value proposition.
                            They do not have an <h1>.
                            They are usually smaller than hero sections (smaller text and shorter height).
                            They always have a headline.
                            They usually have a subheading.
                            They often have a call to action button.
                            They sometimes have one or more images.

                            block-category="rate-highlight":
                            If the component appears to be a section that highlights interest rates (APY or APR), but not in a table, then assign the value "rate-highlight". Interest rates can be displayed in many forms, but the following elements are common:
                            They always have interest rates (APY or APR).
                            They usually have text accompanying the rates.

                            block-category="rate-table":
                            If the component appears to be a table of interest rates (APY or APR), then assign the value "rate-table".

                            block-category="other-table":
                            If the component appears to be a table, but not used for interest rates, then assign the value "other-table".

                            block-category="calculator":
                            If the component appears to be a calculator or estimator, then assign the value "calculator". Calculators and estimators come in many forms, but the following elements are common:
                            They always have input fields.
                            They always output a number.

                            block-category="how-it-works":
                            If the component appears to be a how-it-works section, then assign the value "how-it-works". How-it-works sections come in many forms, but the following elements are common:
                            They typically outline multiple steps
                            They explain a process or method for doing something.

                            block-category="testimonials":
                            If the component appears to be a testimonials or reviews section, then assign the value "testimonials". Testimonials come in many forms, but the following elements are common:
                            They always have quoted text from at least one person talking about how great the product or service is.
                            They often have a photo of the person giving the testimonial.

                            block-category="faqs":
                            If the component appears to be a Frequently Asked Questions (FAQs) section, then assign the value "faqs". FAQs come in many forms, but the following elements are common:
                            They contain questions paired with answers.

                            block-category="blogs":
                            If the component appears to call for links to blog posts or articles, then assign the value "blogs". Blog sections come in many forms, but the following elements are common:
                            They do not contain the full blog content. Rather, the section provides links to the blog posts.
                            The links to the blog posts often come with a short excerpt from the blog.
                            Sometimes the links are accompanied by images.

                            block-category="resources":
                            If the component appears to call for resources or helpful links, then assign the value "resources". Resources come in many forms, but the following elements are common:
                            They always have link text.
                            They sometimes have a short description.

                            block-category="cta":
                            If the component appears to be a call to action (CTA) section, then assign the value "cta". CTA sections often look like hero sections, they do not have an <h1>.

                            block-category="contact-info":
                            If the component appears to focus on contact information, then assign the value "contact-info". Contact information, come in many forms, but the following elements are common:
                            Contact form
                            Email addresses
                            Phone numbers
                            Link to live chat

                            block-category="text-single-column":
                            If the component appears to call for two or more paragraphs of text in a single column, then assign the value "text-single-column".

                            block-category="text-multi-column":
                            If the component appears to call for two or more paragraphs of text organized into multiple columns, then assign the value "text-multi-column".

                            block-category="stats":
                            If the component appears to be a stats section, then assign the value "stats". Stats come in many forms, but the following elements are common:
                            They always have numbers.
                            They usually have accompanying text to explain the numbers.

                            block-category="logos":
                            If the component appears to be a section for featuring logos, then assign the value "logos". Logo sections come in many forms, but the following elements are common:
                            They always contain images of logos.
                            They sometimes contain a heading and subheading.

                            block-category="comparison-table":
                            If the component appears to be a section for comparing two or more products or services, then assign the value "comparison-table". Comparison tables come in many forms, but the following elements are common:
                            They always contain information about at least two products or services.
                            They usually list various features and show how the comparisons stack up.

                            block-category="team":
                            If the component appears to be a team section, then assign the value "team". Team sections come in many forms, but the following elements are common:
                            They usually have images of people.
                            They usually have names of people.
                            They usually contain text that tells you a little bit about the people.

                            block-category="disclosures":
                            If the component appears to be focused on disclosures or legal content, then assign the value "disclosures". Disclosures usually come at the end of a page and are displayed in a text block or accordion element.

                            block-category="other":
                            If the component does not appear to match any of the other block categories above, then assign the value "other". However, before you can make this assignment, you must double check that the component does not match any other block category.
                            ',
                            message: $combinedCode
                        );

                        // Extract the HTML/CSS section from the Grok response
                        $htmlCssSections = preg_match('/```html(.*?)```/s', $htmlCss, $matches) ? trim($matches[1]) : '';

                        if (empty($htmlCssSections)) {
                            Log::info('Failed to extract HTML from Grok response');
                            $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: Failed to convert components</section>';
                        }

                    } catch (\Exception $e) {
                        Log::error('Grok conversion failed: ' . $e->getMessage());
                        $htmlCssSections = '<section id="section-' . time() . '" class="error">Error: Grok conversion failed - ' . $e->getMessage() . '</section>';
                    }
                }
            }

            // Update the recommendation with the converted section
            $built = $recommendation->sections_built + 1;
            $recommendation->update([
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $htmlCssSections . "\n",
            ]);

            // If there are more sections to build, dispatch a new instance of the job
            if ($built < $recommendation->sections_count) {
                self::dispatch($recommendation);
                return;
            }
            
            // Done, no more sections to build
            $recommendation->update([
                'status' => 'done',
            ]);

        } catch (\Exception $e) {
            Log::error('Page generation failed: ' . $e->getMessage());
            
            // Capture error and create an HTML fallback
            $errorHtml = '<section id="section-' . time() . '" class="error">MagicPatterns Error: ' . $e->getMessage() . '</section>';
            $built = $recommendation->sections_built + 1;
            
            $recommendation->update([
                'sections_built' => $built,
                'prototype' => $recommendation->prototype . $errorHtml . "\n",
                'status' => $built < $recommendation->sections_count ? $this->name . '_in_progress' : 'done',
            ]);

            // Continue with the next section if applicable
            if ($built < $recommendation->sections_count) {
                self::dispatch($recommendation);
                return;
            }
        }

        return;
    }
}