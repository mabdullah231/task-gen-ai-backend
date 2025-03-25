<?php

namespace App\Http\Controllers;

use App\Models\AIConfig;
use App\Models\UserReport;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class AIConfigController extends Controller
{
    /**
     * Add or update AI configuration.
     */
    public function manageAIConfig(Request $request)
    {
        try {
            $request->validate([
                'api_key' => 'required|string',
                'model' => 'required|string',
                'temperature' => 'required|numeric|between:0,1',
            ]);

            // Fetch the existing config or create a new one
            $config = AIConfig::firstOrNew([]);

            // Update the config
            $config->api_key = $request->api_key;
            $config->model = $request->model;
            $config->temperature = $request->temperature;
            $config->save();

            return response()->json([
                'message' => 'AI configuration saved successfully.',
                'data' => $config,
            ], 200); // HTTP 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save AI configuration.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Fetch the current AI configuration.
     */
    public function viewAIConfig()
    {
        try {
            $config = AIConfig::first();

            // Set default values if no config exists
            if (!$config) {
                $config = [
                    'api_key' => '',
                    'model' => 'gpt-3.5-turbo', // Default free model
                    'temperature' => 0.5, // Default temperature
                ];
            }

            return response()->json([
                'data' => $config,
            ], 200); // HTTP 200 OK
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'AI configuration not found.',
                'error' => $e->getMessage(),
            ], 404); // HTTP 404 Not Found
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch AI configuration.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    public function generateContent(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'text' => 'required|string',
                'plant_type_id' => 'required|exists:plant_types,id',
            ]);

            // Fetch the AI configuration
            $config = AIConfig::firstOrFail();
            $apiKey = $config->api_key;

            // Extract plant name and assessment responses from input
            $userInput = $request->text;

$expertPrompt = <<<EOT

Here's the information:

$userInput


Your response must be clear, professional, and concise. Use markdown formatting with headings, bullet points, and emphasis where appropriate. Focus on the most likely diagnosis and avoid unnecessary details.
EOT;

            // Create the payload for the API request using the new format
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $expertPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topP' => 0.95,
                    'topK' => 40
                ]
            ];

            // Send a POST request to the Gemini API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", $payload);

            // Check if the response is successful
            // if ($response->successful()) {
            //     // Return the content from the response
            //     return response()->json([
            //         'data' => $response->json()['candidates'][0]['content']['parts'][0]['text'],
            //     ], 200); // HTTP 200 OK
            // } else {
            //     return response()->json([
            //         'message' => 'Failed to generate content.',
            //         'error' => $response->json(),
            //     ], 500); // HTTP 500 Internal Server Error
            // }
            if ($response->successful()) {
                $aiDiagnosis = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                

            
                return response()->json([
                    'message' => 'Diagnosis saved successfully.',
                    'data' => $aiDiagnosis,
                ], 200); // HTTP 200 OK
            } else {
                return response()->json([
                    'message' => 'Failed to generate content.',
                    'error' => $response->json(),
                ], 500); // HTTP 500 Internal Server Error
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'AI configuration not found.',
                'error' => $e->getMessage(),
            ], 404); // HTTP 404 Not Found
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while generating content.',
                'error' => $e->getMessage(),
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
