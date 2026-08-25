<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'need' => ['required', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['ip_address'] = $request->ip();
        Inquiry::query()->create($data);

        return response()->json(['message' => 'Permintaan berhasil dikirim.'], 201);
    }
}
