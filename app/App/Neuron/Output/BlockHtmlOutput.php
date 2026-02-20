<?php

namespace DDD\App\Neuron\Output;

use NeuronAI\StructuredOutput\SchemaProperty;

class BlockHtmlOutput
{
    #[SchemaProperty(description: 'The HTML content of the block wrapped in a section tag', required: true)]
    public string $html;

    #[SchemaProperty(description: 'The block category (hero, feature-list, cta, etc.)', required: true)]
    public string $category;
}
