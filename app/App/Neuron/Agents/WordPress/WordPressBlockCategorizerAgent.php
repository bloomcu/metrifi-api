<?php

namespace DDD\App\Neuron\Agents\WordPress;

use DDD\App\Neuron\Output\WordPressBlockCategoryOutput;
use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\HttpClientOptions;
use NeuronAI\Providers\OpenAI\OpenAI;

class WordPressBlockCategorizerAgent extends Agent
{
    /**
     * Valid `acf_fc_layout--layout` combinations available in the connected
     * WordPress theme. Mirror of the unique entries in
     * metrifi-ui/src/domain/wordpress/store/wordpressBlockSchemas.js.
     *
     * Keep this list in sync with the frontend schemas. If a new schema is
     * added there, add it here too.
     */
    private const VALID_BLOCK_IDS = [
        'accordion_repeater--default',
        'calculator--default',
        'card_repeater--v9',
        'card_three_column_shadow--default',
        'cards_vertical--default',
        'columns_repeater--default',
        'comparison_table--default',
        'details_repeater--details_list_col_3',
        'double_feature--feature_v9',
        'double_feature--feature_v9_bloom_custom_1',
        'feature--boxed_feature',
        'feature--boxed_feature_invert',
        'feature--feature',
        'feature--feature_bloom_1',
        'feature--feature_bloom_1_invert',
        'feature--feature_invert',
        'feature--feature_v11',
        'feature--feature_v11_bottom_center',
        'feature--feature_v11_right',
        'feature--feature_v11_top_center',
        'feature--feature_v2',
        'feature--feature_v2_invert',
        'feature--feature_v4',
        'feature--feature_v4_invert',
        'feature_benefits--feature',
        'feature_benefits--feature_invert',
        'feature_devices--mobile',
        'feature_devices--mobile_and_desktop',
        'feature_repeater--feature_v7',
        'feature_repeater--feature_v9',
        'feature_repeater--feature_v9_centered',
        'feature_repeater--feature_v9_small_icon_centered',
        'feature_repeater--feature_v9_small_image',
        'feature_video--default',
        'feature_video--reverse',
        'gallery_repeater--advanced_gallery_v2',
        'gallery_repeater--gallery',
        'hero--boxed',
        'hero--center',
        'hero--coming-soon',
        'hero--coming-soon-reverse',
        'hero--default',
        'hero--image-fixed-size',
        'hero--image-fixed-size-lg',
        'hero--image-fixed-size-lg-reverse',
        'hero--image-fixed-size-reverse',
        'hero--left-content',
        'hero_immersive--default',
        'hero_search--default',
        'html--default',
        'icon_repeater--default',
        'locator--default',
        'login--angle',
        'login--angle_left',
        'login--angle_one_col',
        'login--angle_one_col_right',
        'login--default',
        'login--login_left',
        'looping_tabs--default',
        'process_repeater--default',
        'products_overview--default',
        'rate_repeater--centered',
        'rate_repeater--default',
        'router--default',
        'section_divider--curve_2',
        'section_divider--curve_3',
        'section_divider--default',
        'section_divider--paint',
        'sticky_hero--default',
        'sub_navigation--sub_navigation',
        'sub_navigation--sub_navigation_collapse',
        'sub_navigation_breadcrumbs--sub_navigation_breadcrumbs',
        'table--default',
        'table_repeater--default',
        'testimonial--full_width_blockquote',
        'testimonial_repeater--cards',
        'testimonial_repeater--slider',
        'text--default',
        'video_hero--video_background',
        'video_hero--video_background_hero_modal',
    ];

    public static function validBlockIds(): array
    {
        return self::VALID_BLOCK_IDS;
    }

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
        $list = implode("\n", array_map(fn ($id) => "- {$id}", self::VALID_BLOCK_IDS));

        return <<<PROMPT
You are an expert at categorizing HTML blocks for a WordPress theme that uses ACF flexible content layouts.

Given the HTML of a single page section, choose the single best matching block ID from the list below.

A block ID is formatted as `<acf_fc_layout>--<layout>`. Return the chosen block ID split into its two parts:
- `type` = the part before `--`
- `layout` = the part after `--`

Both `type` and `layout` MUST come from one of the valid block IDs below. Do not invent new values. Do not return HTML or any other text in either field. If nothing seems to fit perfectly, choose the closest match (commonly `text--default` or `html--default`).

Valid block IDs:
{$list}
PROMPT;
    }

    protected function getOutputClass(): string
    {
        return WordPressBlockCategoryOutput::class;
    }
}
