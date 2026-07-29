<?php

use App\Models\LoginSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
//Broadcast::channel('login.{uuid}', function ($user, $uuid) {
//    return LoginSession::where('uuid', $uuid)->exists();
//});
