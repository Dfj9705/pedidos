<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller responsible for managing customers.
 */
class CustomerController extends Controller
{
    /**
     * Display a listing of customers or render the index view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'customers' => Customer::orderByDesc('id')->get(),
            ], 200);
        }

        return view('customers.index');
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:120', 'unique:customers,email'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'message' => 'Cliente creado',
            'customer' => $customer,
        ], 200);
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  Request  $request
     * @param  Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:120',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $customer->update($data);

        return response()->json([
            'message' => 'Cliente actualizado',
            'customer' => $customer,
        ], 200);
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'message' => 'Cliente eliminado',
        ], 200);
    }
}
