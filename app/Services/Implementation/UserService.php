<?php

namespace App\Services\Implementation;

use App\Models\User;
use App\Services\Interface\UserServiceInterface;

class UserService implements UserServiceInterface
{
    public function all()
    {
        return User::all();
    }

    public function find($id)
    {
        return User::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return true;
    }
}