<?php

namespace DDD\App\Neuron\Output;

use NeuronAI\StructuredOutput\SchemaProperty;

class WordPressBlockCategoryOutput
{
    #[SchemaProperty(description: 'The acf_fc_layout slug, e.g. "hero", "feature", "cta". Must be the part before the "--" in the chosen data-block-id.', required: true)]
    public string $type;

    #[SchemaProperty(description: 'The layout slug, e.g. "default", "centered", "feature_v9". Must be the part after the "--" in the chosen data-block-id.', required: true)]
    public string $layout;
}
