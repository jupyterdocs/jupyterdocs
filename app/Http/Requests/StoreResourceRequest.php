<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:125', 'max:5000'],
            'resource_type_id' => ['required', 'exists:resource_types,id'],
            'university' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt'],
            'confirm_ownership' => ['accepted'],
            'uploader_name' => [$this->user() ? 'nullable' : 'required', 'string', 'max:255'],
            'uploader_email' => ['nullable', 'email', 'max:255'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'thumbnail_data' => ['nullable', 'string', 'max:2000000', 'regex:/^data:image\/[a-zA-Z]+;base64,/'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_ownership.accepted' => 'You must confirm you have the right to distribute this material.',
            'description.required' => 'Please add a description — it helps other students find this document.',
            'description.min' => 'Your description needs to be at least :min characters (it\'s currently shorter) — a fuller description helps other students find this document.',
        ];
    }
}
