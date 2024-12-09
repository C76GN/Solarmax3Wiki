<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Template;

return new class extends Migration
{
    public function up(): void
    {
        // 获取所有现有模板
        $templates = Template::all();

        // 更新每个模板的字段结构
        foreach ($templates as $template) {
            $fields = $template->fields;
            $enhancedFields = [];

            foreach ($fields as $field) {
                // 增强字段结构，保留原有属性
                $enhancedFields[] = [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'required' => $field['required'] ?? false,
                    // 新增属性
                    'description' => '', // 字段描述
                    'default_value' => null, // 默认值
                    'validation_rules' => $this->getDefaultValidationRules($field['type']), // 验证规则
                    'options' => [], // 选项（用于select、radio等类型）
                    'placeholder' => '', // 占位文本
                    'help_text' => '', // 帮助文本
                ];
            }

            // 更新模板
            $template->fields = $enhancedFields;
            $template->save();
        }
    }

    public function down(): void
    {
        // 回滚时简化字段结构
        $templates = Template::all();

        foreach ($templates as $template) {
            $fields = $template->fields;
            $simplifiedFields = [];

            foreach ($fields as $field) {
                $simplifiedFields[] = [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'required' => $field['required'] ?? false,
                ];
            }

            $template->fields = $simplifiedFields;
            $template->save();
        }
    }

    private function getDefaultValidationRules($type)
    {
        switch ($type) {
            case 'text':
                return [
                    'min' => 0,
                    'max' => 255,
                    'pattern' => ''
                ];
            case 'number':
                return [
                    'min' => null,
                    'max' => null,
                    'step' => 1
                ];
            case 'date':
                return [
                    'min_date' => null,
                    'max_date' => null
                ];
            case 'markdown':
                return [
                    'min_length' => 0,
                    'max_length' => 50000
                ];
            default:
                return [];
        }
    }
};