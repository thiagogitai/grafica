<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FlyerController extends Controller
{
    public function index()
    {
        $pricesPath = base_path('precos_flyer.json');
        $pricesData = json_decode(file_get_contents($pricesPath), true);

        return view('flyers.index', ['prices' => $pricesData]);
    }
}
