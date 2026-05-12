<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.bidding_management');
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('quote.index', compact('pageTitle', 'auth_user', 'assets', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Quote::with(['booking', 'provider'])->orderBy('id', 'desc');

        if (filter_var($request->status, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('status', $request->status);
        }

        return $datatable->eloquent($query)
            ->editColumn('provider_id', function ($quote) {
                return $quote->provider ? $quote->provider->display_name : '-';
            })
            ->editColumn('booking_id', function ($quote) {
                return '<a href="'.route('booking.show', $quote->booking_id).'">#'.$quote->booking_id.'</a>';
            })
            ->editColumn('price', function ($quote) {
                return getPriceFormat($quote->price);
            })
            ->editColumn('status', function ($quote) {
                $status = $quote->status;
                $color = 'primary';
                if ($status == 'pending') $color = 'warning';
                if ($status == 'approved') $color = 'success';
                if ($status == 'rejected') $color = 'danger';
                return '<span class="badge bg-'.$color.'">'.ucfirst($status).'</span>';
            })
            ->rawColumns(['booking_id', 'status'])
            ->toJson();
    }
}
