<?php

namespace DDD\App\Neuron;

class RecommendationPrompts
{
    protected static ?array $prompts = null;

    public static function get(string $key): string
    {
        if (self::$prompts === null) {
            $path = storage_path('app/assistant_prompts.json');
            self::$prompts = file_exists($path)
                ? json_decode(file_get_contents($path), true) ?? []
                : [];
        }

        return self::$prompts[$key] ?? self::getDefault($key);
    }

    protected static function getDefault(string $key): string
    {
        return match ($key) {
            'comparison_analyzer' => 'You are an expert at analyzing web page screenshots. Compare the focus page with higher-performing comparison pages and provide actionable recommendations. Title your analysis "Comparison Analysis".',
            'synthesizer' => 'Review all information and write a comprehensive report. Title your analysis "Comprehensive Analysis".',
            'anonymizer' => 'Find the "Comprehensive Analysis" report and remove any breaches of the Anonymity Rule. Re-output the anonymized report only.',
            'write_content_outline' => 'Create an extremely detailed content outline titled "Content Outline" for a maximum-converting web page.',
            default => 'Complete your instructions.',
        };
    }
}
