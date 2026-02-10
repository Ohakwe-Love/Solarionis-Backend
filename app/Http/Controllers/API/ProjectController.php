<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Offering;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Get all projects (with filters)
     */
    public function index(Request $request)
    {
        $query = Project::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Only show projects that are not draft
        $query->whereIn('status', ['funding', 'active', 'completed']);

        $projects = $query->get()->map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'type' => $project->type,
                'description' => $project->description,
                'location' => $project->location . ', ' . $project->location_state,
                'capacity' => (float) $project->capacity,
                'funding_goal' => (float) $project->funding_goal,
                'current_funding' => (float) $project->current_funding,
                'funding_progress' => $project->funding_progress,
                'expected_return' => (float) $project->expected_annual_return,
                'duration_months' => $project->duration_months,
                'status' => $project->status,
                'completion_percentage' => $project->completion_percentage,
                'investors_count' => $project->investors_count,
                'image_url' => $project->image_url,
                'highlights' => $project->highlights,
                'has_active_offering' => $project->active_offering !== null,
            ];
        });

        return response()->json([
            'projects' => $projects
        ], 200);
    }

    /**
     * Get single project by slug
     */
    public function show($slug)
    {
        $project = Project::where('slug', $slug)->first();

        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'type' => $project->type,
                'description' => $project->description,
                'location' => $project->location,
                'location_state' => $project->location_state,
                'capacity' => (float) $project->capacity,
                'total_cost' => (float) $project->total_cost,
                'funding_goal' => (float) $project->funding_goal,
                'current_funding' => (float) $project->current_funding,
                'funding_progress' => $project->funding_progress,
                'expected_return' => (float) $project->expected_annual_return,
                'duration_months' => $project->duration_months,
                'status' => $project->status,
                'completion_percentage' => $project->completion_percentage,
                'investors_count' => $project->investors_count,
                'project_start_date' => $project->project_start_date,
                'expected_completion_date' => $project->expected_completion_date,
                'image_url' => $project->image_url,
                'highlights' => $project->highlights,
                'documents' => $project->documents,
            ]
        ], 200);
    }

    /**
     * Get active offering for a project
     */
    public function activeOffering($projectId)
    {
        $offering = Offering::where('project_id', $projectId)
            ->active()
            ->first();

        if (!$offering) {
            return response()->json([
                'message' => 'No active offering found for this project'
            ], 404);
        }

        return response()->json([
            'offering' => [
                'id' => $offering->id,
                'project_id' => $offering->project_id,
                'share_price' => (float) $offering->share_price,
                'min_investment' => (float) $offering->min_investment,
                'opens_at' => $offering->opens_at,
                'closes_at' => $offering->closes_at,
                'status' => $offering->status,
                'total_shares' => $offering->total_shares,
                'shares_sold' => $offering->shares_sold,
                'shares_available' => $offering->shares_available,
                'funding_progress' => $offering->funding_progress,
                'is_active' => $offering->is_active,
            ]
        ], 200);
    }
}