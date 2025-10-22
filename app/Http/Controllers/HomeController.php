<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index()
    {
        $totals = [
            'categories' => Category::count(),
            'brands' => Brand::count(),
            'products' => Product::count(),
            'customers' => Customer::count(),
        ];

        return view('home', compact('totals'));
    }
}
