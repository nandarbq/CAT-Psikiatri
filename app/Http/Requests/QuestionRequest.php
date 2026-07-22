<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Question|null $question */
        $question = $this->route('question');
        $moduleId = $question?->module_id;
        $maxOrder = $moduleId !== null
            ? max(1, (int) Question::query()->where('module_id', $moduleId)->count())
            : 1;

        return [
            'question' => ['required', 'string', 'max:10000'],
            'order_no' => ['required', 'integer', 'min:1', 'max:'.$maxOrder],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
