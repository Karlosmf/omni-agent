<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = ['purpose', 'name', 'language', 'body', 'variables_count', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'variables_count' => 'integer'];
    }

    /**
     * The active template approved for a purpose, if the agency registered one.
     */
    public static function forPurpose(string $purpose): ?self
    {
        return static::where('purpose', $purpose)->where('is_active', true)->first();
    }

    /**
     * Preview of the template with its variables filled in.
     *
     * @param  list<string>  $variables
     */
    public function render(array $variables): string
    {
        $text = $this->body;

        foreach (array_values($variables) as $i => $value) {
            $text = str_replace('{{'.($i + 1).'}}', $value, $text);
        }

        return $text;
    }
}
