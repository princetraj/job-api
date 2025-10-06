<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Get all content (Admin only)
     */
    public function index(Request $request)
    {
        $query = Content::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $contents = $query->latest()->paginate(20);

        return response()->json([
            'contents' => $contents,
        ], 200);
    }

    /**
     * Get single content by ID or slug
     */
    public function show($identifier)
    {
        $content = Content::where('id', $identifier)
            ->orWhere('slug', $identifier)
            ->first();

        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        return response()->json([
            'content' => $content,
        ], 200);
    }

    /**
     * Create new content (Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string|in:page,blog,faq,terms,privacy',
            'status' => 'nullable|string|in:draft,published',
            'meta_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $slug = Str::slug($request->title);
        $originalSlug = $slug;
        $counter = 1;

        // Ensure unique slug
        while (Content::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $content = Content::create([
            'id' => Str::uuid(),
            'title' => $request->title,
            'slug' => $slug,
            'body' => $request->body,
            'type' => $request->type,
            'status' => $request->status ?? 'draft',
            'meta_data' => $request->meta_data,
        ]);

        return response()->json([
            'message' => 'Content created successfully',
            'content' => $content,
        ], 201);
    }

    /**
     * Update content (Admin only)
     */
    public function update(Request $request, $id)
    {
        $content = Content::find($id);

        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'type' => 'sometimes|string|in:page,blog,faq,terms,privacy',
            'status' => 'sometimes|string|in:draft,published',
            'meta_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only(['title', 'body', 'type', 'status', 'meta_data']);

        // Update slug if title changed
        if (isset($updateData['title'])) {
            $slug = Str::slug($updateData['title']);
            $originalSlug = $slug;
            $counter = 1;

            while (Content::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $updateData['slug'] = $slug;
        }

        $content->update($updateData);

        return response()->json([
            'message' => 'Content updated successfully',
            'content' => $content->fresh(),
        ], 200);
    }

    /**
     * Delete content (Admin only)
     */
    public function destroy($id)
    {
        $content = Content::find($id);

        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        $content->delete();

        return response()->json([
            'message' => 'Content deleted successfully',
        ], 200);
    }
}
