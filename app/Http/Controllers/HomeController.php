<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the public landing page with the active services.
     */
    public function index(): View
    {
        return view('home', [
            'services' => Service::where('is_active', true)
                ->orderBy('price')
                ->get(),
        ]);
    }
}
