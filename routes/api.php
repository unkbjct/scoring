<?php

use App\Webhooks\Scoring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/points', function (Request $request) {
    $request->validate([
        'lead_id' => 'required',
    ]);
    
    $leadId = (int) explode("_", $request->lead_id)[1];
    return response()->json(Scoring::handler($leadId));
});