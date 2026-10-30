<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('rag.upload.max_kb');
        $exts = implode(',', config('rag.upload.allowed_extensions'));

        return [
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:'.$maxKb, 'extensions:'.$exts, 'mimetypes:'.implode(',', config('rag.upload.allowed_mimes'))],
            'is_guideline' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        $maxMb = round((int) config('rag.upload.max_kb') / 1024);

        return [
            'files.required' => 'Pilih minimal satu dokumen.',
            'files.max' => 'Maksimal 20 file dalam satu kali unggah.',
            'files.*.max' => "Ukuran setiap file maksimal {$maxMb} MB.",
            'files.*.extensions' => 'Format file harus PDF, TXT, DOC, atau DOCX.',
            'files.*.mimetypes' => 'Isi file tidak sesuai dengan format yang didukung.',
            'files.*.file' => 'Terjadi kesalahan saat mengunggah file.',
            'files.*.uploaded' => 'File gagal diunggah. Periksa batas upload_max_filesize di php.ini.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
