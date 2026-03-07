<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class ImportMapping implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;


    private array $targetFields;
    private array $sourceFields;
    /**
     * Allowed source fields (Feed Keys)
     */
    public function setSourceFields(array $sourceFields): static
    {
        $this->sourceFields = $sourceFields;
        return $this;
    }

    /**
     * Allowed target fields
     */
    public function setTargetFields(array $targetFields): static
    {
        $this->targetFields = $targetFields;
        return $this;
    }

    /**
     * Build dynamic instructions
     */
    public function instructions(): Stringable|string
    {
        $sourceFields = json_encode($this->sourceFields, JSON_PRETTY_PRINT);
        $targetFields = json_encode($this->targetFields, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a data mapping assistant.

Your job:
Map source fields to target fields.

CRITICAL RULES:
- Only use fields from the provided lists
- Never invent fields
- Never guess meanings
- Mapping decisions are based ONLY on semantic similarity of field names
- DO NOT consider value constraints, formats, validation rules, or uniqueValues
- If two fields represent the same concept, ALWAYS map them
- Omit unmapped fields entirely
- Always return ALL required keys

VALID SOURCE FIELDS:
$sourceFields

VALID TARGET FIELDS:
$targetFields

OUTPUT FORMAT:
{
  "field_mappings": [
    {
      "source_field": "...",
      "target_field": "...",
      "transformation": "none",
      "transformation_params": [],
      "value_mapping": [],
      "required": false,
      "default_value": null,
      "validation_rules": []
    }
  ]
}


MESSAGE RULES (VERY IMPORTANT):

The message MUST contain:

1) Mapping Summary
Explain WHY each source field was mapped to its target field.

Example style:
- "Mapped 'Make' → 'make' because both represent vehicle manufacturer"

2) Unmapped Fields
Explain WHY certain fields were NOT mapped.

Example style:
- "'DCID' was not mapped because no equivalent target field exists"

3) Ambiguities (if any)
Mention fields that could match multiple targets.

4) Confidence Statement
Provide an overall confidence level.

The explanation must be clear, concise, and technical.

PROMPT;
    }

    public function messages(): iterable
    {
        return [];
    }

    public function tools(): iterable
    {
        return [];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'field_mappings' => $schema->array()->items(
                $schema->object([
                    'source_field' => $schema->string()->required(),
                    'target_field' => $schema->string()->required(),
                    'transformation' => $schema->string()->required(),
                    'required' => $schema->boolean()->required(),
                    'default_value' => $schema->string()->required(),
                ])->withoutAdditionalProperties()
            )->required(),
            'message' => $schema->string()->required(),
        ];
    }
}
