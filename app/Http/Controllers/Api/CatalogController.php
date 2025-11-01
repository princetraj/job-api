<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Location;
use App\Models\JobCategory;
use App\Models\Skill;
use App\Models\Degree;
use App\Models\University;
use App\Models\FieldOfStudy;
use App\Models\EducationLevel;
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
     * Get all skills (approved only for public, all for admins)
     */
    public function getSkills(Request $request)
    {
        // Check if user is authenticated and is admin
        $user = $request->user();

        // Check if user is admin (using guard or model type)
        $isAdmin = false;
        if ($user) {
            $isAdmin = $user instanceof \App\Models\Admin ||
                       get_class($user) === 'App\Models\Admin' ||
                       (method_exists($user, 'getTable') && $user->getTable() === 'admins');
        }

        // Admins can see all skills with status filter, others only see approved
        if ($isAdmin && $request->has('status')) {
            $status = $request->status;
            if (in_array($status, ['approved', 'pending', 'rejected'])) {
                $skills = Skill::where('approval_status', $status)->orderBy('created_at', 'desc')->get();
            } else {
                $skills = Skill::orderBy('created_at', 'desc')->get();
            }
        } elseif ($isAdmin) {
            // Admins see all skills if no filter specified
            $skills = Skill::orderBy('created_at', 'desc')->get();
        } else {
            // Non-admins only see approved skills
            $skills = Skill::approved()->orderBy('name', 'asc')->get();
        }

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

        $admin = $request->user();

        $skill = Skill::create([
            'name' => $request->name,
            'approval_status' => 'approved', // Admin-created skills are auto-approved
            'created_by' => $admin ? $admin->id : null,
            'created_by_type' => 'admin',
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

    /**
     * Approve skill (Admin)
     */
    public function approveSkill(Request $request, $id)
    {
        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $skill->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Skill approved successfully',
            'skill' => $skill,
        ], 200);
    }

    /**
     * Reject skill (Admin)
     */
    public function rejectSkill(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $skill = Skill::find($id);

        if (!$skill) {
            return response()->json(['message' => 'Skill not found'], 404);
        }

        $skill->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'Skill rejected',
            'skill' => $skill,
        ], 200);
    }

    /**
     * Get pending skills (Admin)
     */
    public function getPendingSkills(Request $request)
    {
        $skills = Skill::pending()->orderBy('created_at', 'desc')->get();
        return response()->json(['skills' => $skills], 200);
    }

    // ================= DEGREES =================

    /**
     * Get all degrees (approved only for public, all for admins)
     */
    public function getDegrees(Request $request)
    {
        $user = $request->user();
        $isAdmin = false;
        if ($user) {
            $isAdmin = $user instanceof \App\Models\Admin ||
                       get_class($user) === 'App\Models\Admin' ||
                       (method_exists($user, 'getTable') && $user->getTable() === 'admins');
        }

        if ($isAdmin && $request->has('status')) {
            $status = $request->status;
            if (in_array($status, ['approved', 'pending', 'rejected'])) {
                $degrees = Degree::where('approval_status', $status)->orderBy('created_at', 'desc')->get();
            } else {
                $degrees = Degree::orderBy('created_at', 'desc')->get();
            }
        } elseif ($isAdmin) {
            $degrees = Degree::orderBy('created_at', 'desc')->get();
        } else {
            $degrees = Degree::approved()->orderBy('name', 'asc')->get();
        }

        return response()->json(['degrees' => $degrees], 200);
    }

    /**
     * Create degree (Admin: Catalog Manager / Super Admin)
     */
    public function createDegree(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:degrees,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->user();

        $degree = Degree::create([
            'name' => $request->name,
            'approval_status' => 'approved',
            'created_by' => $admin ? $admin->id : null,
            'created_by_type' => 'admin',
        ]);

        return response()->json([
            'message' => 'Degree created',
            'degree' => $degree,
        ], 201);
    }

    /**
     * Update degree (Admin: Catalog Manager / Super Admin)
     */
    public function updateDegree(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:degrees,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $degree = Degree::find($id);

        if (!$degree) {
            return response()->json(['message' => 'Degree not found'], 404);
        }

        $degree->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Degree updated',
            'degree' => $degree,
        ], 200);
    }

    /**
     * Delete degree (Admin: Super Admin)
     */
    public function deleteDegree(Request $request, $id)
    {
        $degree = Degree::find($id);

        if (!$degree) {
            return response()->json(['message' => 'Degree not found'], 404);
        }

        $degree->delete();

        return response()->json(['message' => 'Degree deleted'], 200);
    }

    /**
     * Approve degree (Admin)
     */
    public function approveDegree(Request $request, $id)
    {
        $degree = Degree::find($id);

        if (!$degree) {
            return response()->json(['message' => 'Degree not found'], 404);
        }

        $degree->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Degree approved successfully',
            'degree' => $degree,
        ], 200);
    }

    /**
     * Reject degree (Admin)
     */
    public function rejectDegree(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $degree = Degree::find($id);

        if (!$degree) {
            return response()->json(['message' => 'Degree not found'], 404);
        }

        $degree->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'Degree rejected',
            'degree' => $degree,
        ], 200);
    }

    // ================= UNIVERSITIES =================

    /**
     * Get all universities (approved only for public, all for admins)
     */
    public function getUniversities(Request $request)
    {
        $user = $request->user();
        $isAdmin = false;
        if ($user) {
            $isAdmin = $user instanceof \App\Models\Admin ||
                       get_class($user) === 'App\Models\Admin' ||
                       (method_exists($user, 'getTable') && $user->getTable() === 'admins');
        }

        if ($isAdmin && $request->has('status')) {
            $status = $request->status;
            if (in_array($status, ['approved', 'pending', 'rejected'])) {
                $universities = University::where('approval_status', $status)->orderBy('created_at', 'desc')->get();
            } else {
                $universities = University::orderBy('created_at', 'desc')->get();
            }
        } elseif ($isAdmin) {
            $universities = University::orderBy('created_at', 'desc')->get();
        } else {
            $universities = University::approved()->orderBy('name', 'asc')->get();
        }

        return response()->json(['universities' => $universities], 200);
    }

    /**
     * Create university (Admin: Catalog Manager / Super Admin)
     */
    public function createUniversity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:universities,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->user();

        $university = University::create([
            'name' => $request->name,
            'approval_status' => 'approved',
            'created_by' => $admin ? $admin->id : null,
            'created_by_type' => 'admin',
        ]);

        return response()->json([
            'message' => 'University created',
            'university' => $university,
        ], 201);
    }

    /**
     * Update university (Admin: Catalog Manager / Super Admin)
     */
    public function updateUniversity(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:universities,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $university = University::find($id);

        if (!$university) {
            return response()->json(['message' => 'University not found'], 404);
        }

        $university->update(['name' => $request->name]);

        return response()->json([
            'message' => 'University updated',
            'university' => $university,
        ], 200);
    }

    /**
     * Delete university (Admin: Super Admin)
     */
    public function deleteUniversity(Request $request, $id)
    {
        $university = University::find($id);

        if (!$university) {
            return response()->json(['message' => 'University not found'], 404);
        }

        $university->delete();

        return response()->json(['message' => 'University deleted'], 200);
    }

    /**
     * Approve university (Admin)
     */
    public function approveUniversity(Request $request, $id)
    {
        $university = University::find($id);

        if (!$university) {
            return response()->json(['message' => 'University not found'], 404);
        }

        $university->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'University approved successfully',
            'university' => $university,
        ], 200);
    }

    /**
     * Reject university (Admin)
     */
    public function rejectUniversity(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $university = University::find($id);

        if (!$university) {
            return response()->json(['message' => 'University not found'], 404);
        }

        $university->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'University rejected',
            'university' => $university,
        ], 200);
    }

    // ================= FIELD OF STUDIES =================

    /**
     * Get all fields of study (approved only for public, all for admins)
     */
    public function getFieldOfStudies(Request $request)
    {
        $user = $request->user();
        $isAdmin = false;
        if ($user) {
            $isAdmin = $user instanceof \App\Models\Admin ||
                       get_class($user) === 'App\Models\Admin' ||
                       (method_exists($user, 'getTable') && $user->getTable() === 'admins');
        }

        if ($isAdmin && $request->has('status')) {
            $status = $request->status;
            if (in_array($status, ['approved', 'pending', 'rejected'])) {
                $fieldOfStudies = FieldOfStudy::where('approval_status', $status)->orderBy('created_at', 'desc')->get();
            } else {
                $fieldOfStudies = FieldOfStudy::orderBy('created_at', 'desc')->get();
            }
        } elseif ($isAdmin) {
            $fieldOfStudies = FieldOfStudy::orderBy('created_at', 'desc')->get();
        } else {
            $fieldOfStudies = FieldOfStudy::approved()->orderBy('name', 'asc')->get();
        }

        return response()->json(['field_of_studies' => $fieldOfStudies], 200);
    }

    /**
     * Create field of study (Admin: Catalog Manager / Super Admin)
     */
    public function createFieldOfStudy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:field_of_studies,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = $request->user();

        $fieldOfStudy = FieldOfStudy::create([
            'name' => $request->name,
            'approval_status' => 'approved',
            'created_by' => $admin ? $admin->id : null,
            'created_by_type' => 'admin',
        ]);

        return response()->json([
            'message' => 'Field of study created',
            'field_of_study' => $fieldOfStudy,
        ], 201);
    }

    /**
     * Update field of study (Admin: Catalog Manager / Super Admin)
     */
    public function updateFieldOfStudy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:field_of_studies,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fieldOfStudy = FieldOfStudy::find($id);

        if (!$fieldOfStudy) {
            return response()->json(['message' => 'Field of study not found'], 404);
        }

        $fieldOfStudy->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Field of study updated',
            'field_of_study' => $fieldOfStudy,
        ], 200);
    }

    /**
     * Delete field of study (Admin: Super Admin)
     */
    public function deleteFieldOfStudy(Request $request, $id)
    {
        $fieldOfStudy = FieldOfStudy::find($id);

        if (!$fieldOfStudy) {
            return response()->json(['message' => 'Field of study not found'], 404);
        }

        $fieldOfStudy->delete();

        return response()->json(['message' => 'Field of study deleted'], 200);
    }

    /**
     * Approve field of study (Admin)
     */
    public function approveFieldOfStudy(Request $request, $id)
    {
        $fieldOfStudy = FieldOfStudy::find($id);

        if (!$fieldOfStudy) {
            return response()->json(['message' => 'Field of study not found'], 404);
        }

        $fieldOfStudy->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Field of study approved successfully',
            'field_of_study' => $fieldOfStudy,
        ], 200);
    }

    /**
     * Reject field of study (Admin)
     */
    public function rejectFieldOfStudy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fieldOfStudy = FieldOfStudy::find($id);

        if (!$fieldOfStudy) {
            return response()->json(['message' => 'Field of study not found'], 404);
        }

        $fieldOfStudy->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'Field of study rejected',
            'field_of_study' => $fieldOfStudy,
        ], 200);
    }

    // ================= EDUCATION LEVELS =================

    /**
     * Get all education levels
     */
    public function getEducationLevels(Request $request)
    {
        $status = $request->query('status'); // Filter by status if provided

        $query = EducationLevel::query()->orderBy('order')->orderBy('name');

        if ($status) {
            $query->where('status', $status);
        }

        $educationLevels = $query->get();

        return response()->json(['education_levels' => $educationLevels], 200);
    }

    /**
     * Create education level (Admin only)
     */
    public function createEducationLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:education_levels,name',
            'status' => 'nullable|in:active,inactive',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $educationLevel = EducationLevel::create([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'message' => 'Education level created',
            'education_level' => $educationLevel,
        ], 201);
    }

    /**
     * Update education level (Admin only)
     */
    public function updateEducationLevel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:education_levels,name,' . $id,
            'status' => 'sometimes|in:active,inactive',
            'order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $educationLevel = EducationLevel::find($id);

        if (!$educationLevel) {
            return response()->json(['message' => 'Education level not found'], 404);
        }

        $educationLevel->update($request->only(['name', 'status', 'order']));

        return response()->json([
            'message' => 'Education level updated',
            'education_level' => $educationLevel,
        ], 200);
    }

    /**
     * Delete education level (Admin only)
     */
    public function deleteEducationLevel(Request $request, $id)
    {
        $educationLevel = EducationLevel::find($id);

        if (!$educationLevel) {
            return response()->json(['message' => 'Education level not found'], 404);
        }

        // Check if any employee education is using this level
        $usageCount = $educationLevel->employeeEducations()->count();

        if ($usageCount > 0) {
            return response()->json([
                'message' => 'Cannot delete education level as it is being used by ' . $usageCount . ' education record(s)',
            ], 400);
        }

        $educationLevel->delete();

        return response()->json(['message' => 'Education level deleted'], 200);
    }
}
