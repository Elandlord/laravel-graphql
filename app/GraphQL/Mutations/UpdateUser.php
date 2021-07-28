<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    public function update($root, array $args): User
    {
        $user = User::findOrFail($args['id']);

        $user->name = $args['name'];

        if (in_array('password', $args)) {
            $user->password = Hash::make($args['password']);
        }

        $user->save();
        return $user;
    }
}
