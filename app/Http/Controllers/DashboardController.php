<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    protected $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function home(Request $request)
    {
        $user = $request->user();
        $month = $request->query('month');
        $year = $request->query('year');
        $data = $this->service->getHomeData($user, $month, $year);
        return response()->json($data);
    }
}