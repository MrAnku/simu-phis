<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BrandMonitoringController extends Controller
{
   public function index(){

    return view('brand-monitoring');
   }


    // Fetch domains for a specific scan
    public function fetchDomains($sid)
    {
        try {
            $response = Http::get(env('BRAND_MONITORING_SERVER') . '/' . $sid . '/domains');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to fetch domains'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Poll scan status
    public function pollScan($sid)
    {
        try {
            $response = Http::get(env('BRAND_MONITORING_SERVER') . '/' . $sid);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to poll scan'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Create a new scan
    public function createScan(Request $request)
    {
        try {
            $response = Http::post(env('BRAND_MONITORING_SERVER'), $request->json()->all());

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to create scan'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Stop an ongoing scan
    public function stopScan($sid)
    {
        try {
            $response = Http::post(env('BRAND_MONITORING_SERVER') . '/' . $sid . '/stop');

            if ($response->successful()) {
                return response()->json(['message' => 'Scan stopped successfully']);
            }

            return response()->json(['error' => 'Failed to stop scan'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Get a list of scanned permutations
    public function getScanList($sid)
    {
        try {
            $response = Http::get(env('BRAND_MONITORING_SERVER') . '/' . $sid . '/list');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to fetch scan list'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Download scan results as CSV
    public function downloadCSV($sid)
    {
        try {
            $response = Http::get(env('BRAND_MONITORING_SERVER') . '/' . $sid . '/csv');

            if ($response->successful()) {
                return response($response->body(), 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="scan_results.csv"',
                ]);
            }

            return response()->json(['error' => 'Failed to download CSV'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    // Download scan results as JSON
    public function downloadJSON($sid)
    {
        try {
            $response = Http::get(env('BRAND_MONITORING_SERVER') . '/' . $sid . '/json');

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to download JSON'], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }
}
