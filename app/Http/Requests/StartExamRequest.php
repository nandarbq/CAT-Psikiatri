<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartExamRequest extends FormRequest
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
        $tokenBypassEnabled = (bool) config(
            'exam.token_bypass_enabled',
            false
        );

        $maxAccuracyMeters = (float) config(
            'exam.max_accuracy_meters',
            150
        );

        return [
            'token' => $tokenBypassEnabled
                ? [
                    'nullable',
                    'string',
                    'max:80',
                ]
                : [
                    'required',
                    'digits:6',
                ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],


            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'accuracy' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . $maxAccuracyMeters,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.digits' =>
                'Token tes harus berupa 6 digit angka.',

            'latitude.numeric' =>
                'Latitude harus berupa angka.',

            'longitude.numeric' =>
                'Longitude harus berupa angka.',

            'accuracy.numeric' =>
                'Accuracy lokasi harus berupa angka.',

            'accuracy.min' =>
                'Accuracy lokasi tidak boleh bernilai negatif.',

            'accuracy.max' =>
                'Akurasi lokasi perangkat terlalu rendah ' .
                'untuk digunakan sebagai verifikasi lokasi.',
        ];
    }
}