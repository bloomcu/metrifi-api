<?php

namespace DDD\App\Neuron\Output;

use NeuronAI\StructuredOutput\SchemaProperty;
use NeuronAI\StructuredOutput\Validation\Rules\ArrayOf;

class ContentOutlineSections
{
    /**
     * @var \DDD\App\Neuron\Output\ContentOutlineSection[]
     */
    #[SchemaProperty(description: 'Array of sections from the content outline', required: true)]
    #[ArrayOf(ContentOutlineSection::class)]
    public array $sections;
}
