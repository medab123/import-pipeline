<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set plaintext tokens on existing pipelines.
     * These tokens were previously stored in the organization_tokens table;
     * this migration copies the original plaintext values directly onto each pipeline row.
     */
    public function up(): void
    {
        $orgPipelines = [
            ['pipeline_id' => 18, 'token' => 'org_mom05ONDS6WFt4rZldLvjAIeL4IF3znXqBxjXZRX'],
            ['pipeline_id' => 19, 'token' => 'org_XKU3jJbA2MNTJ6sYIFrLHdPGp7LvgW0DyeqoQC5Z'],
            ['pipeline_id' => 20, 'token' => 'org_pRKv4TdSZO1oXhE9lzCNBIYqaHJm7WuGb8VFDx2n'],
            ['pipeline_id' => 21, 'token' => 'org_cQn8RbYh3WuKLvGxAP5JeOTfzM4iX0sD1mI6Ew7N'],
            ['pipeline_id' => 22, 'token' => 'org_yL7fZKs0JnApW9RxT3HbM6DqVuY4GEci1Ow5Xe2N'],
            ['pipeline_id' => 23, 'token' => 'org_1Pz6IhKXaW3vNJLsBy9oT8cGMRq2YdFr5EU0eOQu'],
            ['pipeline_id' => 24, 'token' => 'org_6aWLhY2KjOz3PNnRsBcXI9dFV0qE7UmGt1AeovT5'],
            ['pipeline_id' => 25, 'token' => 'org_FQ3kIaJwP7HdYm6EtSX0R9lNzBgc2VeouW5CKx4n'],
            ['pipeline_id' => 26, 'token' => 'org_Dv5sXzRnJKyL1GMpa7EoqTB2iW3OHfc0Uj9dN8eC'],
            ['pipeline_id' => 27, 'token' => 'org_nC4gIJPyxL0sB3RKo2TzW6EqFmaDHvN8fU5eOdVj'],
            ['pipeline_id' => 28, 'token' => 'org_9TxHoGK4kCzWqR7JvdN0YPmeI2UsXaL3bF5DOjnB'],
            ['pipeline_id' => 29, 'token' => 'org_A2lEjYRTHNp4wZk7xCs9GdBqVmu6i0fOXon3eK1W'],
            ['pipeline_id' => 30, 'token' => 'org_7HJzRFsK5bWqxY3m0TNao9EpGuvLi4X2ecBOdjC6'],
            ['pipeline_id' => 31, 'token' => 'org_vGb4WNasX0cqz3fROTuL7IKpJ9kY2mjHDexi5n1E'],
            ['pipeline_id' => 32, 'token' => 'org_oKX1YmHj3LnTqB0pzaG8Dce6W4RNIusV7xO2FEdi'],
            ['pipeline_id' => 33, 'token' => 'org_U5pz3kWYsOHmLJ6nXDGe9CqIbTaR2vjxo0NF4E7d'],
            ['pipeline_id' => 34, 'token' => 'org_MsI2XEwKz8uYoJqNvR4f0DbcOBHglj7T5Cp6nLAe'],
            ['pipeline_id' => 35, 'token' => 'org_P9tRnJzXaY6vWi0EqB3sDGo8Kl5hN1mZCFu7xTeO'],
            ['pipeline_id' => 36, 'token' => 'org_B0eGxKfWpL4cIjT8Rn2OqszYvXd5Um7HaDNh9EoJ'],
            ['pipeline_id' => 37, 'token' => 'org_w3NhXqP7OmYRsTz0JkeLaVbD4Ic9GuE5fxB6oZj2'],
            ['pipeline_id' => 38, 'token' => 'org_LzJu1aRN8oYKe4Tp2HmGXvs5BD0CFqjIbxW7nOf6'],
        ];

        foreach ($orgPipelines as $entry) {
            DB::table('import_pipelines')
                ->where('id', $entry['pipeline_id'])
                ->update(['token' => $entry['token']]);
        }
    }

    public function down(): void
    {
        // Clear the tokens that were set by this migration
        $pipelineIds = [18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38];

        DB::table('import_pipelines')
            ->whereIn('id', $pipelineIds)
            ->update(['token' => null]);
    }
};
