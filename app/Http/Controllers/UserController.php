<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserNetwork;
use Response;
use Uuid;
use DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        $users = $users->map(function($user){
            $fb = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 1)->first();
            $in = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 4)->first();
            $total = DB::table('bubble_users_total_scores')->where('user_id', $user->id)->first();
            $user->score_fb = isset($fb) ? $fb->score : 0;
            $user->score_in = isset($in) ? $in->score : 0;
            $user->total_score = isset($total) ? $total->score : 0;
            return $user;
        });
        return Response::json($users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {        
        $authUser = $this->findOrCreateUser($request);

        $tokenResult = $authUser->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);

        return Response::json(["token" => $token]);
    }

    public function findOrCreateUser($user)
    {
        $authUser = User::where('email', $user->email)->first();
        if ($authUser) {
            return $user;
        }

        $user = User::create([
            'name' => $user->name,
            'email' => $user->email,
            'profile' =>  $user->picture,
            'uuid' => Uuid::generate(),
            'location' => 'agadir',
            'locality' => 'agadir',

        ]);

        UserNetwork::create([
            'user_id' => $user->id,
            'network_id' => $provider->type_network,
            'access_keys' => "{'userID' : ".$provider->id.",'accessKey' : ".$provider->accessToken."}",
        ]);

        return $user;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with(['score.network', 'totalScore'])->where('uuid', $id)->first();

        $keyed = $user->score->keyBy('network.network_name');

        $user->score = $keyed->all();
        return Response::json($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::where('uuid', $id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'about' => $request->about,
        ]);

        $user = User::with('score')->where('uuid', $id)->first();
        return Response::json($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
