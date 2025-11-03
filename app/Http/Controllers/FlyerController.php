<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class FlyerController extends Controller
{
    public function index()
    {
        $pricesPath = base_path('precos_flyer.json');
        $pricesData = json_decode(file_get_contents($pricesPath), true);

        $requestOnlyGlobal = Setting::boolean('request_only', false);

        return view('flyers.index', [
            'prices' => $pricesData,
            'requestOnlyGlobal' => $requestOnlyGlobal,
        ]);
    }
}
