<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields',
        'is_system'
    ];

    protected $casts = [
        'fields' => 'array',
        'is_system' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function getFields(): array
    {
        return $this->fields ?? [];
    }

    public function getValidationRules(): array
    {
        $rules = [];
        
        foreach ($this->fields as $field) {
            $fieldRules = $this->buildFieldValidationRules($field);
            if (!empty($fieldRules)) {
                $rules["content.{$field['name']}"] = $fieldRules;
            }
        }

        return $rules;
    }

    protected function buildFieldValidationRules(array $field): array
    {
        $rules = [];

        // 必填规则
        if ($field['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // 根据字段类型添加验证规则
        switch ($field['type']) {
            case 'text':
                $rules[] = 'string';
                if (isset($field['validation_rules']['min'])) {
                    $rules[] = "min:{$field['validation_rules']['min']}";
                }
                if (isset($field['validation_rules']['max'])) {
                    $rules[] = "max:{$field['validation_rules']['max']}";
                }
                if (!empty($field['validation_rules']['pattern'])) {
                    $rules[] = "regex:/{$field['validation_rules']['pattern']}/";
                }
                break;

            case 'number':
                $rules[] = 'numeric';
                if (isset($field['validation_rules']['min'])) {
                    $rules[] = "min:{$field['validation_rules']['min']}";
                }
                if (isset($field['validation_rules']['max'])) {
                    $rules[] = "max:{$field['validation_rules']['max']}";
                }
                break;

            case 'date':
                $rules[] = 'date';
                if (!empty($field['validation_rules']['min_date'])) {
                    $rules[] = "after_or_equal:{$field['validation_rules']['min_date']}";
                }
                if (!empty($field['validation_rules']['max_date'])) {
                    $rules[] = "before_or_equal:{$field['validation_rules']['max_date']}";
                }
                break;

            case 'boolean':
                $rules[] = 'boolean';
                break;

            case 'markdown':
                $rules[] = 'string';
                if (isset($field['validation_rules']['min_length'])) {
                    $rules[] = "min:{$field['validation_rules']['min_length']}";
                }
                if (isset($field['validation_rules']['max_length'])) {
                    $rules[] = "max:{$field['validation_rules']['max_length']}";
                }
                break;
        }

        return $rules;
    }
}