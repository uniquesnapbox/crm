<?php

namespace App\Modules\WhatsApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function send(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $result = $whatsAppService->sendMessage($validated['number'], $validated['message']);

        return response()->json($result);
    }
}

