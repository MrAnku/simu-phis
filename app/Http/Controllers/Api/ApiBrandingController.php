<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\CompanyBranding;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApiBrandingController extends Controller
{
    public function index()
    {
        $branding = CompanyBranding::where('company_id', Auth::user()->company_id)->first();
        return response()->json([
            'success' => true,
            'message' => 'Branding material retrieved successfully',
            'data' => $branding,
        ]);
    }

    public function addBranding(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string|max:255',
                'favicon' => 'required|image',
                'light_logo' => 'required|image',
                'dark_logo' => 'required|image',
            ]);
            
            //check if branding already exists for the company
            $existingBranding = CompanyBranding::where('company_id', Auth::user()->company_id)->first();
            if ($existingBranding) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branding material already exists for this company',
                ], 400);
            }

            $branding = new CompanyBranding();
            $branding->company_name = $request->input('company_name');
            
            // Generate 24 character alphanumeric filename for favicon
            $faviconName = bin2hex(random_bytes(12)) . '.' . $request->file('favicon')->getClientOriginalExtension();
            $branding->favicon = "/" .$request->file('favicon')->storeAs('whiteLabel/' . Auth::user()->company_id, $faviconName, 's3');
            
            // Generate 24 character alphanumeric filename for light logo
            $lightLogoName = bin2hex(random_bytes(12)) . '.' . $request->file('light_logo')->getClientOriginalExtension();
            $branding->light_logo = "/" .$request->file('light_logo')->storeAs('whiteLabel/' . Auth::user()->company_id, $lightLogoName, 's3');
            
            // Generate 24 character alphanumeric filename for dark logo
            $darkLogoName = bin2hex(random_bytes(12)) . '.' . $request->file('dark_logo')->getClientOriginalExtension();
            $branding->dark_logo = "/" . $request->file('dark_logo')->storeAs('whiteLabel/' . Auth::user()->company_id, $darkLogoName, 's3');
            
            $branding->company_id = Auth::user()->company_id;
            $branding->save();

            return response()->json([
                'success' => true,
                'message' => 'Branding material added successfully',
                'data' => $branding,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function updateBranding(Request $request)
    {
        try{
            $request->validate([
                'company_name' => 'sometimes|required|string|max:255',
                'favicon' => 'sometimes|required|image',
                'light_logo' => 'sometimes|required|image',
                'dark_logo' => 'sometimes|required|image',
            ]);
            
            $branding = CompanyBranding::where('company_id', Auth::user()->company_id)->first();
            if (!$branding) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branding material not found',
                ], 404);
            }

            if ($request->has('company_name')) {
                $branding->company_name = $request->input('company_name');
            }

            if ($request->hasFile('favicon')) {
                $faviconName = bin2hex(random_bytes(12)) . '.' . $request->file('favicon')->getClientOriginalExtension();
                $branding->favicon = "/" . $request->file('favicon')->storeAs('whiteLabel/' . Auth::user()->company_id, $faviconName, 's3');
            }

            if ($request->hasFile('light_logo')) {
                $lightLogoName = bin2hex(random_bytes(12)) . '.' . $request->file('light_logo')->getClientOriginalExtension();
                $branding->light_logo = "/" . $request->file('light_logo')->storeAs('whiteLabel/' . Auth::user()->company_id, $lightLogoName, 's3');
            }

            if ($request->hasFile('dark_logo')) {
                $darkLogoName = bin2hex(random_bytes(12)) . '.' . $request->file('dark_logo')->getClientOriginalExtension();
                $branding->dark_logo = "/" . $request->file('dark_logo')->storeAs('whiteLabel/' . Auth::user()->company_id, $darkLogoName, 's3');
            }

            $branding->save();

            return response()->json([
                'success' => true,
                'message' => 'Branding material updated successfully',
                'data' => $branding,
            ]);

        }catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteBranding()
    {
        try{
            $branding = CompanyBranding::where('company_id', Auth::user()->company_id)->first();
            if (!$branding) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branding material not found',
                ], 404);
            }

            //delete the assets from s3
            if ($branding->favicon) {
                Storage::disk('s3')->delete(ltrim($branding->favicon, '/'));
            }
            if ($branding->light_logo) {
                Storage::disk('s3')->delete(ltrim($branding->light_logo, '/'));
            }
            if ($branding->dark_logo) {
                Storage::disk('s3')->delete(ltrim($branding->dark_logo, '/'));
            }   

            $branding->delete();

            return response()->json([
                'success' => true,
                'message' => 'Branding material deleted successfully',
            ]);

        }catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation Error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }
}
