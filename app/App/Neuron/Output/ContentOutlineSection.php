<?php

namespace DDD\App\Neuron\Output;

use NeuronAI\StructuredOutput\SchemaProperty;

class ContentOutlineSection
{
    #[SchemaProperty(description: 'The outline text for this section', required: true)]
    public string $outline;
}
