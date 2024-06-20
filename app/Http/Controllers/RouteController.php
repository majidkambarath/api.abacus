<?php
namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        $routes = Route::with(['locations' => function ($query) {
            $query->withCount(['businesses as business_count' => function ($subquery) {
                $subquery->select(DB::raw('count(distinct landphone)'));
            }]);
        }])->orderBy('id', 'desc')->get();

        // Calculate the total_business_count for each route
        $routes->each(function ($route) {
            $totalBusinessCount = $route->locations->sum('business_count');
            $route->total_business_count = $totalBusinessCount;
        });

        return response()->json(['routes' => $routes]);
    }


    public function show(Route $route)
    {
        $route->load('locations');
        return response()->json(['route' => $route]);
    }

    public function store(Request $request)
    {
        $route = Route::create(['name' => $request->name]);
        if($request->locations) {
            $locations = $request->locations;
            foreach ($locations as $location){
                Location::create(['name' => $location['name'], 'route_id' => $route->id]);
            }

        }
        $route->load('locations');
        return response()->json(['route' => $route], 201);
    }

    public function update(Request $request, Route $route)
    {
        // Update the route name
        $route->update(['name' => $request->name]);
        // Reload the route with the updated locations
        $route->load('locations');

        return response()->json(['route' => $route]);
    }


    public function destroy(Route $route)
    {
        $route->delete();
        return response()->json(null, 204);
    }

    public function storeLocation(Request $request, Route $route)
    {
        if($request->locations) {
            $locations = $request->locations;
            foreach ($locations as $location){
                $route->locations()->create(['name' => $location['name']]);
            }
        }

        return response()->json('done', 201);
    }

    public function updateLocation(Request $request, Route $route, Location $location)
    {
        $location->update(['name' => $request->name]);
        return response()->json(['location' => $location]);
    }

    public function destroyLocation(Location $location)
    {
        $location->delete();
        return response()->json(null, 204);
    }
}
