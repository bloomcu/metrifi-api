<?php

namespace DDD\App\Console\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Recommendations\Recommendation;
use DDD\Domain\Pages\Page;
use DDD\Domain\Blocks\Block;
use DOMDocument;
use DOMXPath;

class ConvertPrototypesToPagesAndBlocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prototypes:convert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert recommendation prototypes html to new pages with blocks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recommendations = Recommendation::whereNotNull('prototype')->get();
        
        $this->info("Found {$recommendations->count()} recommendations with prototypes to convert");
        
        $progressBar = $this->output->createProgressBar($recommendations->count());
        $progressBar->start();
        
        foreach ($recommendations as $recommendation) {
            // Create a new page for each recommendation
            $page = Page::create([
                'organization_id' => $recommendation->organization_id,
                'user_id' => $recommendation->user_id,
                'title' => $recommendation->title,
                'recommendation_id' => $recommendation->id,
            ]);
            
            // Parse the HTML to extract sections and outermost divs
            $html = $recommendation->prototype;
            
            // Use DOMDocument to parse the HTML
            $dom = new DOMDocument();
            // Suppress warnings for malformed HTML
            libxml_use_internal_errors(true);
            $dom->loadHTML('<div id="wrapper">' . $html . '</div>');
            libxml_clear_errors();
            
            // Use XPath to find all section elements and outermost divs
            $xpath = new DOMXPath($dom);
            
            // Get the wrapper element
            $wrapper = $xpath->query('//div[@id="wrapper"]')->item(0);
            
            // Get all direct children of the wrapper
            $blockElements = [];
            
            if ($wrapper) {
                foreach ($wrapper->childNodes as $child) {
                    // Only process element nodes (type 1) that are divs or sections
                    if ($child->nodeType === 1 && ($child->nodeName === 'section' || $child->nodeName === 'div')) {
                        $blockElements[] = $child;
                    }
                }
            }
            
            // If no direct children were found, try to find all sections
            if (empty($blockElements)) {
                $sections = $xpath->query('//section');
                foreach ($sections as $section) {
                    $blockElements[] = $section;
                }
            }
            
            // Create a block for each element
            $order = 1;
            foreach ($blockElements as $element) {
                // Get the HTML content of the element
                $elementHtml = $dom->saveHTML($element);
                
                // Create a new block
                Block::create([
                    'page_id' => $page->id,
                    'organization_id' => $recommendation->organization_id,
                    'user_id' => $recommendation->user_id,
                    'html' => $elementHtml,
                    'order' => $order++,
                ]);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info('Conversion completed successfully!');
    }
}
