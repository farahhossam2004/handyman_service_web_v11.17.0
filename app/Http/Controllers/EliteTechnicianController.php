<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class EliteTechnicianController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = __('messages.elite_technician_analytics');
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('elite.index', compact('pageTitle', 'auth_user', 'assets'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = User::where('user_type', 'provider')->with('getServiceRating');

        return $datatable->eloquent($query)
            ->addColumn('rating', function ($provider) {
                return round($provider->getServiceRating->avg('rating'), 1);
            })
            ->editColumn('is_elite', function ($provider) {
                $checked = $provider->is_elite ? 'checked' : '';
                return '<div class="form-check form-switch"><input type="checkbox" class="form-check-input change_elite_status" data-id="'.$provider->id.'" id="elite_'.$provider->id.'" '.$checked.'></div>';
            })
            ->rawColumns(['is_elite'])
            ->toJson();
    }

    public function toggleElite(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->is_elite = $request->is_elite;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => __('messages.elite_status_updated_successfully')
        ]);
    }
}
