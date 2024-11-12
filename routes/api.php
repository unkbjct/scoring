<?php

use App\Webhooks\Scoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/points', function (Request $request) {

    $request->validate([
        'lead_id' => 'required',
        'phone' => 'required|array|min:1',
    ]);

    return response()->json(Scoring::handler($request->lead_id, $request->phone));
});
