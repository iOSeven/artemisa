<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Module;

class WelcomeController extends Controller
{
    public function index(){
        $modules_info = Module::get();
        
        return view('welcome', compact('modules_info'));
    }
}
