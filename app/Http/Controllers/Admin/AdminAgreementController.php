<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalAgreement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminAgreementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|demo_admin');
    }

    public function index()
    {
        $pageTitle = __('messages.legal_agreements');
        $customerAgreement = LegalAgreement::ofType('customer_agreement')->active()->latest('version')->first();
        $providerAgreement = LegalAgreement::ofType('provider_agreement')->active()->latest('version')->first();

        return view('admin.agreements.index', compact('pageTitle', 'customerAgreement', 'providerAgreement'));
    }

    public function edit($id)
    {
        $agreement = LegalAgreement::findOrFail($id);
        $pageTitle = __('messages.legal_agreement_edit');

        return view('admin.agreements.edit', compact('agreement', 'pageTitle'));
    }

    public function update(Request $request, $id)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $agreement = LegalAgreement::findOrFail($id);

        $request->validate([
            'content_ar' => 'required|string',
            'content_en' => 'required|string',
        ]);

        $agreement->update([
            'content_ar' => $request->content_ar,
            'content_en' => $request->content_en,
        ]);

        return redirect()->route('admin.agreements.index')
            ->withSuccess(__('messages.legal_agreement_update'));
    }
}
