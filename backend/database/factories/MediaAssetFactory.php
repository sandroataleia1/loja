<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Media\Enums\MediaTypeEnum;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
final class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'type'          => MediaTypeEnum::Image,
            'path'          => 'media/image/' . $this->faker->uuid() . '.jpg',
            'original_name' => $this->faker->word() . '.jpg',
            'mime_type'     => 'image/jpeg',
            'file_size'     => $this->faker->numberBetween(50000, 5000000),
            'is_active'     => true,
            'uploaded_by'   => null,
            'metadata'      => null,
        ];
    }

    public function image(): static
    {
        return $this->state(['type' => MediaTypeEnum::Image, 'mime_type' => 'image/jpeg']);
    }

    public function video(): static
    {
        return $this->state([
            'type'      => MediaTypeEnum::Video,
            'mime_type' => 'video/mp4',
            'path'      => 'media/video/' . $this->faker->uuid() . '.mp4',
        ]);
    }

    public function banner(): static
    {
        return $this->state(['type' => MediaTypeEnum::Banner, 'mime_type' => 'image/webp']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withMetadata(): static
    {
        return $this->state([
            'metadata' => [
                'width'  => $this->faker->numberBetween(400, 4000),
                'height' => $this->faker->numberBetween(400, 4000),
            ],
        ]);
    }
}
