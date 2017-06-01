<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminHomeController extends Controller
{
    public function index(Request $request){
        //do Admin Home View
        return 'Welcome to Admin System.';
    }
}
