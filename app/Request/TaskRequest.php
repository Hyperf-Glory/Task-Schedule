<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'taskId' => 'required',
            'taskNo' => 'required',
            'runtime' => 'nullable|date',
            'content' => 'required|json',
        ];
    }

    /**
     * 字段名称.
     *
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'taskId' => '任务ID',
            'taskNo' => '任务编号',
            'runtime' => '运行时间',
            'content' => '任务内容',
        ];
    }
}
