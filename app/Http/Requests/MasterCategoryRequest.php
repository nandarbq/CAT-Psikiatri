<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MasterCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterCategoryRequest extends FormRequest
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
        /** @var MasterCategory|null $category */
        $category = $this->route('category');
        $categoryId = $category?->id;

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('master_categories', 'name')->ignore($categoryId)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
