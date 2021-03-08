<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Users extends Controller
{
    public function signup (Request $request){
        $data = $request->validate([
            "username" => "required|min:4|max:20|alpha_num|unique:users",
            "email" => "required|email|unique:users",
            "password" => "min:8|required_with:confirmPassword",
            "confirmPassword" => "required|same:password"
        ]);

        $hashedPassword = User::hashPassword($data["password"]);
        $verificationToken = sha1(random_bytes(32));
        $verificationTokenIssued = now();
        $user = [
            "username" => $data["username"],
            "email" => $data["email"],
            "password" => $hashedPassword,
            "verification_token" => $verificationToken,
            "verification_token_issued_at" => $verificationTokenIssued,
        ];
        
        User::create($user);
        
        EmailVerificationController::sendVerificationEmail($user["email"],$user["username"],$verificationToken);
        return response()->json(["msg" => "Account was created, and a verification email has been sent to your email."]);
        
    }

    public function login(Request $request){
        $user = User::where("email",$request->email)->first();
        if($user->is_verified != true){
            return response()->json(["msg" => "Please verify your email, before trying to login"]);
        }
        $credentials = $request->only("email","password");
        if(Auth::attempt($credentials)){
            return response()->json(["status" => "success"]);
        }
    }
}
