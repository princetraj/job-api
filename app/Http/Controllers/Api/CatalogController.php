<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Location;
use App\Models\JobCategory;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CatalogController extends Controller
{
    // ================= INDUSTRIES =================

    /**
     * Get all industries
     */
    public function getIndustries(Request $request)
    {
        $industries = Industry::all();
        return response()->json(['industries' => $industries], 200);
    }

    /**
     * Create industry (Admin: Catalog Manager / Super Admin)
     */
    public function createIndustry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:industries,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $industry = Industry::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Industry created',
            'industry' => $industry,
        ], 201);
    }

    /**
     * Update industry (Admin: Catalog Manager / Super Admin)
     */
    public function updateIndustry(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:industries,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $industry = Industry::find($id);

        if (!$industry) {
            return response()->json(['message' => 'Industry not found'], 404);
        }

        $industry->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Industry updated',
            'industry' => $industry,
        ], 200);
    }

    /**
     * Delete industry (Admin: Super Admin)
     */
    public function deleteIndustry(Request $request, $id)
    {
        $industry = Industry::find($id);

        if (!$industry) {
            return response()->json(['message' => 'Industry not found'], 404);
        }

        $industry->delete();

        return response()->json(['message' => 'Industry deleted'], 200);
    }

    // ================= LOCATIONS =================

    /**
     * Get all locations
     */
    public function getLocations(Request $request)
    {
        $locations = Location::all();
        return response()->json(['locations' => $locations], 200);
    }

    /**
     * Create location (Admin: Catalog Manager / Super Admin)
     */
    public function createLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = Location::create([
            'name' => $request->name,
            'state' => $request->state,
            'country' => $request->country,
        ]);

        return response()->json([
            'message' => 'Location created',
            'location' => $location,
        ], 201);
    }

    /**
     * Update location (Admin: Catalog Manager / Super Admin)
     */
    public function updateLocation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $location->update($request->only(['name', 'state', 'country']));

        return response()->json([
            'message' => 'Location updated',
            'location' => $location,
        ], 200);
    }

    /**
     * Delete location (Admin: Super Admin)
     */
    public function deleteLocation(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        $location->delete();

        return response()->json(['message' => 'Location deleted'], 200);
    }

    // ================= JOB CATEGORIES =================

    /**
     * Get all job categories
     */
    public function getJobCategories(Request $request)
    {
        $categories = JobCategory::all();
        return response()->json(['categories' => $categories], 200);
    }

    /**
     * Create job category (Admin: Catalog Manager / Super Admin)
     */
    public function createJobCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = JobCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Job category created',
            'category' => $category,
        ], 201);
    }

    /**
     * Update job category (Admin: Catalog Manager / Super Admin)
     */
    public function updateJobCategory(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = JobCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Job category not found'], 404);
        }

        $category->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Job category updated',
            'category' => $category,
        ], 200);
    }

    /**
     * Delete job category (Admin: Super Admin)
     */
    public function deleteJobCategory(Request $request, $id)
    {
        $category = JobCategory::find($id);

        if (!$category) {
            return response()->json(['message' => 'Job category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Job category deleted'], 200);
    }

    // ================= SKILLS =================

    /**
     * Get all skills
     */
    public function getSkills(Request $request)
    {
        $skills = Skill::all();
        return response()->json(['skills' => $skills], 200);
    }

    /**
     * Create skill (Admin: Catalog Manager / Super Admin)
     */
    public function createSkill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:skills,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $skill = Skill::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Skill created',
            'skill' => $skill,
        ], 201);
    }

    /**
     * Update skill (Admin: Catalog Manager / Super Admin)
     */
    public function updateSkill(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:skills,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $skill->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Skill updated',
            'skill' => $skill,
        ], 200);
    }

    /**
     * Delete skill (Admin: Super Admin)
     */
    public function deleteSkill(Request $request, $id)
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $skill->delete();

        return response()->json(['message' => 'Skill deleted'], 200);
    }
}
