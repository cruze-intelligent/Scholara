<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolStatusController extends Controller
{
    public function show(Request $request): View
    {
        return view('school-status.show', ['school' => $request->user()->school]);
    }
}
