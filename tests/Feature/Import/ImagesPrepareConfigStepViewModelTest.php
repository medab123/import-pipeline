<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\ImagesPrepareConfigStepViewModel;
use App\Models\Organization;
use App\Models\User;
use Elaitech\Import\Enums\ImportPipelineStep;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImagesPrepareConfigStepViewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_getters_fall_back_to_defaults_when_stored_config_values_are_null(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_uuid' => $org->uuid]);
        $this->actingAs($user);

        $pipeline = new ImportPipeline;
        $pipeline->forceFill([
            'name' => 'Null Config Pipeline',
            'frequency' => 'once',
            'organization_uuid' => $org->uuid,
        ])->saveQuietly();

        // image_separator, images_key, active and download_mode are all `nullable`
        // in the step's validation, so a saved config can legitimately store nulls.
        $config = new ImportPipelineConfig;
        $config->forceFill([
            'pipeline_id' => $pipeline->id,
            'organization_uuid' => $org->uuid,
            'type' => ImportPipelineStep::ImagesPrepareConfig->value,
            'config_data' => [
                'image_indexes_to_skip' => null,
                'image_separator' => null,
                'images_key' => null,
                'active' => null,
                'download_mode' => null,
            ],
        ])->saveQuietly();

        $viewModel = new ImagesPrepareConfigStepViewModel($pipeline);

        // Before the fix these returned the stored null and tripped their typed
        // return signatures (TypeError: imageSeparator() must be of type string).
        $this->assertSame(',', $viewModel->imageSeparator());
        $this->assertFalse($viewModel->active());
        $this->assertSame('all', $viewModel->downloadMode());
        $this->assertSame('images', $viewModel->imagesKey());
        $this->assertSame([], $viewModel->imageIndexesToSkip());
    }
}
