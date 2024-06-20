<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Business;
use App\Models\BusinessPhoto;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function updatePhotos(Request $request)
    {

        $photos = BusinessPhoto::get();
        $leads = [];
        foreach ($photos as $photo){
            $lead = Lead::find($photo->business_id);
            $photo->business_id = $lead->business_id;
            $photo->save();
        }

        return response()->json(['$leads' => $leads]);

    }
//    public function deleteControllerFolder(Request $request)
//    {
//        // Add some validation to ensure the request is authorized (e.g., API token, authentication, etc.)
//
//        try {
//            // Define the path to the 'Controller' folder
//            $folderPath = app_path('Http/Controllers/Controller');
//
//            // Check if the folder exists
//            if (File::exists($folderPath)) {
//                // Delete the folder recursively
//                File::deleteDirectory($folderPath);
//
//                return response()->json(['message' => 'Controller folder deleted successfully'], 200);
//            }
//
//            return response()->json(['error' => 'Controller folder does not exist'], 404);
//        } catch (\Exception $e) {
//            return response()->json(['error' => 'Failed to delete Controller folder'], 500);
//        }
//    }
}
