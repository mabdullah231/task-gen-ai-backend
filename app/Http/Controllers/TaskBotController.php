<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\AIConfig;

class TaskBotController extends Controller
{
    public function suggestionGeneration(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'description' => 'required|string',
            ]);
    
            $projectDescription = $request->description;
    
            $config = AIConfig::firstOrFail();
            $apiKey = $config->api_key;

            $prompt = <<<EOT
            You are an expert project planner AI assistant. Your task is to read the user's project description and suggest relevant tasks they should complete to build the project.

            First, generate a suitable title for the project in 3 to 5 words based on the description.

            Then, format the response as a JS objects array. Each suggestion should be an object with:
            - "task": A short title for the task
            - "priority": One of "Critical", "High", "Medium", or "Low"
            - "days": Estimated number of days required to complete the task

            Respond only with a JS objects array. Do not include any introduction or explanation but include the title of project in JSON Response.
            If details such as stack (Frontend, backend, database, etc.) are provided, then don't give tasks such as "decide stack" or "framework" etc. Also provide sub-tasks to any major tasks within the task object.
            Here's the project description:
            "{$projectDescription}"

            Generate smart, relevant, and logically ordered but reversed (so project-specific tasks suggestions are shown to user first instead of setup, etc.) task suggestions based on this.
            EOT;

    
            // Build Gemini payload
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.6,
                    'topP' => 0.9,
                    'topK' => 40
                ]
            ];
    
            // Make API request to Gemini
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", $payload);
    
            // Handle response
            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'];
    
                // $cleanedText = preg_replace('/^```javascript\n|\n```$/', '', $text); // Remove the code block markers
                $cleanedText = preg_replace('/^`{3,}(?:javascript|json)?\n|\n`{3,}$/i', '', $text);

                // Decode JSON response from AI
                $suggestions = json_decode($cleanedText, true);
            
    
                if (json_last_error() === JSON_ERROR_NONE && is_array($suggestions)) {

                    $userId = Auth::id();  // Get the authenticated user's ID

                    // Create a new Plan record and store the data
                    $plan = Plan::create([
                        'title' => $suggestions['title'],  // Save project title from AI response
                        'description' => $projectDescription,
                        'json' => $suggestions['tasks'],  // Save tasks in JSON format
                        'user_id' => $userId, // Save the ID of the user who created the plan
                    ]);

                    return response()->json([
                        'message' => 'Suggestions generated successfully.',
                        'data' => $suggestions
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'Failed to parse suggestions from AI response.',
                        'raw' => $text,
                    ], 422);
                }
            } else {
                return response()->json([
                    'message' => 'Failed to generate suggestions.',
                    'error' => $response->json(),
                ], 500);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'AI configuration not found.',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during suggestion generation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
