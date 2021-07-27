<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    $user = User::first();

    if( $user->tokens()->count() === 0) {
        $token = $user->createToken('test-token');
        dd($token->plainTextToken);
    }

    return view('welcome');
});
