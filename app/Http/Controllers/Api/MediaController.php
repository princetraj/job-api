<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Get all media (Admin only)
     */
    public function index(Request $request)
    {
        $query = Media::query();

        if ($request->has('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        $media = $query->latest()->paginate(20);

        return response()->json([
            'media' => $media,
        ], 200);
    }

    /**
     * Get single media file
     */
    public function show($id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        return response()->json([
            'media' => $media,
        ], 200);
    }

    /**
     * Upload media file (Admin only)
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'file_type' => 'required|string|in:image,video,document',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();

        // Store file in public disk
        $path = $file->storeAs('media', $fileName, 'public');

        $media = Media::create([
            'id' => Str::uuid(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => '/storage/' . $path,
            'file_type' => $request->file_type,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by_type' => get_class($request->user()),
            'uploaded_by_id' => $request->user()->id,
            'metadata' => $request->metadata,
        ]);

        return response()->json([
            'message' => 'Media uploaded successfully',
            'media' => $media,
        ], 201);
    }

    /**
     * Update media metadata (Admin only)
     */
    public function update(Request $request, $id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $media->update([
            'metadata' => $request->metadata,
        ]);

        return response()->json([
            'message' => 'Media updated successfully',
            'media' => $media->fresh(),
        ], 200);
    }

    /**
     * Delete media file (Admin only)
     */
    public function destroy($id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        // Delete physical file
        $filePath = str_replace('/storage/', '', $media->file_path);
        Storage::disk('public')->delete($filePath);

        // Delete database record
        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ], 200);
    }
}
