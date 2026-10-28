<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'history_id' => ['nullable', 'integer', 'exists:histories,id'],
            'model_name' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\-\/]*$/'],
            'mode' => ['nullable', 'in:qa,compliance'],
            'document_ids' => ['nullable', 'array', 'max:50'],
            'document_ids.*' => ['integer'],
            'guideline_document_ids' => ['nullable', 'array', 'max:50'],
            'guideline_document_ids.*' => ['integer'],
            'retry_query_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'Pertanyaan tidak boleh kosong.',
            'question.max' => 'Pertanyaan terlalu panjang (maksimal 2000 karakter).',
            'model_name.regex' => 'Nama model tidak valid.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
        ], 422));
    }
}