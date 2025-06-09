<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class PlansController extends Controller
{
    public function getAllPlans(Request $request)
{
    try {
        // Get the authenticated user's ID
        $userId = Auth::id();

        // Retrieve all plans for the authenticated user
        $plans = Plan::where('user_id', $userId)->get();

        // If no plans are found
        if ($plans->isEmpty()) {
            return response()->json([
                'message' => 'No plans found for this user.',
            ], 404);
        }

        // Return only the title and description for each plan
        return response()->json([
            'message' => 'Plans retrieved successfully.',
            'data' => $plans->map(function ($plan) {
                return [
                    'plan_id' => $plan->id,
                    'title' => $plan->title,
                    'description' => $plan->description,
                ];
            }),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while retrieving the plans.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function getSinglePlan(Request $request)
{
    try {
        // Validate the incoming request to ensure 'plan_id' is provided and exists
        $request->validate([
            'plan_id' => 'required|exists:plans,id',  // Check if the plan_id exists in the 'plans' table
        ]);

        $planId = $request->plan_id;

        // Retrieve the plan by ID and ensure it belongs to the authenticated user
        $plan = Plan::where('id', $planId)->where('user_id', Auth::id())->first();

        // If the plan is not found or doesn't belong to the authenticated user
        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found or you do not have permission to view this plan.',
            ], 404);
        }

        // Check if the json field is already decoded
        $tasks = $plan->json;
        if (is_string($tasks)) {
            // Decode only if it's a JSON string
            $tasks = json_decode($tasks, true);
        }

        // Return the plan details along with tasks
        return response()->json([
            'message' => 'Plan retrieved successfully.',
            'data' => [
                'plan_id' => $plan->id,
                'title' => $plan->title,
                'description' => $plan->description,
                'tasks' => $tasks,  // Return the tasks (decoded or already in array format)
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while retrieving the plan.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



}
