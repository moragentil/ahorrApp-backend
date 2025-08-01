<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Interface\UserServiceInterface;

class UserController extends Controller
{
    protected $service;

    public function __construct(UserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return response()->json($this->service->all());
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'string',
            'email' => 'email',
            'password' => 'string|min:6',
        ]);
        return response()->json($this->service->update($id, $data));
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Deleted']);
    }
}