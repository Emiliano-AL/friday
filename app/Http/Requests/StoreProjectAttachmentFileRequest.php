<?php

namespace App\Http\Requests;

use App\Models\ProjectAttachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectAttachmentFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Images and documents have different size limits; the type is decided by
     * the MIME type detected from the file contents, not by its name. Legacy
     * Office files often report a generic OLE MIME type with no mapped
     * extension, so for them the content check is the OLE detection itself.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extensions = implode(',', [...ProjectAttachment::DOCUMENT_EXTENSIONS, ...ProjectAttachment::IMAGE_EXTENSIONS]);

        return [
            'file' => [
                'required',
                'file',
                Rule::when(! $this->isLegacyOfficeFile(), "mimes:{$extensions}"),
                "extensions:{$extensions}",
                'max:'.($this->isImage() ? ProjectAttachment::IMAGE_MAX_KILOBYTES : ProjectAttachment::DOCUMENT_MAX_KILOBYTES),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $types = 'Solo se permiten documentos (PDF, Word, Excel, PowerPoint, TXT, MD, CSV) e imágenes (JPG, PNG, WebP, GIF).';

        return [
            'file.required' => 'Selecciona un archivo.',
            'file.file' => 'El archivo no se pudo subir.',
            'file.uploaded' => 'El archivo no se pudo subir.',
            'file.mimes' => $types,
            'file.extensions' => $types,
            'file.max' => $this->isImage()
                ? 'Las imágenes no pueden superar 5 MB.'
                : 'Los documentos no pueden superar 15 MB.',
        ];
    }

    /**
     * Determine whether the file is a .doc/.xls/.ppt whose content is detected as a generic OLE document.
     */
    private function isLegacyOfficeFile(): bool
    {
        $file = $this->file('file');

        return $file !== null
            && in_array(strtolower($file->getClientOriginalExtension()), ['doc', 'xls', 'ppt'], true)
            && in_array($file->getMimeType(), ['application/x-ole-storage', 'application/CDFV2'], true);
    }

    /**
     * Determine whether the uploaded file is an image, by its detected MIME type.
     */
    public function isImage(): bool
    {
        return str_starts_with((string) $this->file('file')?->getMimeType(), 'image/');
    }
}
