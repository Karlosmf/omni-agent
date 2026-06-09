<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class JsonSlider
{
    public string $name;

    public ?string $description;

    public array $slides;

    public function __construct(string $name, ?string $description = null, array $slides = [])
    {
        $this->name = $name;
        $this->description = $description;
        $this->slides = $slides;
    }

    /**
     * Get the directory where sliders are stored.
     */
    public static function getDirectory(): string
    {
        $directory = public_path('sliders');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return $directory;
    }

    /**
     * Get all sliders.
     *
     * @return Collection<int, JsonSlider>
     */
    public static function all(): Collection
    {
        $directory = self::getDirectory();
        $files = File::files($directory);

        return collect($files)
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->map(function ($file) {
                $name = $file->getBasename('.json');

                return self::find($name);
            })
            ->filter()
            ->values();
    }

    /**
     * Find a slider by name.
     */
    public static function find(string $name): ?self
    {
        $filePath = self::getDirectory().'/'.$name.'.json';

        if (! File::exists($filePath)) {
            return null;
        }

        try {
            $content = File::get($filePath);
            $data = json_decode($content, true);

            if (! is_array($data)) {
                return null;
            }

            return new self(
                name: $name,
                description: $data['description'] ?? null,
                slides: $data['slides'] ?? []
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save the slider to a JSON file.
     */
    public function save(?string $oldName = null): bool
    {
        $directory = self::getDirectory();

        // If the name changed, delete the old file
        if ($oldName && $oldName !== $this->name) {
            $oldPath = $directory.'/'.$oldName.'.json';
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $filePath = $directory.'/'.$this->name.'.json';
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'slides' => $this->slides,
        ];

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return File::put($filePath, $jsonContent) !== false;
    }

    /**
     * Delete the slider JSON file.
     */
    public static function delete(string $name): bool
    {
        $filePath = self::getDirectory().'/'.$name.'.json';

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }
}
