<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InquiryController extends Controller
{
    public function index(): View
    {
        return view('cms.inquiries.index', ['inquiries' => Inquiry::query()->latest()->paginate(20)]);
    }

    public function show(Inquiry $inquiry): View
    {
        return view('cms.inquiries.show', compact('inquiry'));
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $inquiry->update($request->validate(['status' => ['required', 'in:new,contacted,closed']]));
        return back()->with('success', 'Status permintaan diperbarui.');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();
        return redirect()->route('cms.inquiries.index')->with('success', 'Permintaan dihapus.');
    }
}
