<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{

    public function __construct()
    {
    }

    public function welcome()
    {
        return view('welcome');
    }
}
