<?php

namespace App\Http\Handlers;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FilesHandler
{
    public function create($data)
    {
        $file = new File();
        $file->item_id = $data['item_id'];
        $file->name = $data['name'];
        $file->location = $data['location'];
        $file->is_default = $data['is_default'] ?? null;
        $file->save();

        $imageData = str_replace('data:image/png;base64,', '', $data['image_data']);
        $processedImage = str_replace('data:image/jpeg;base64,', '', $imageData);
        Storage::put("public/" . $file->location, base64_decode($processedImage));

        return $file->fresh();
    }
}
