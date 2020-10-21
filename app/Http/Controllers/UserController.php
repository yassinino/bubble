<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserNetwork;
use App\Score;
use Response;
use Uuid;
use DB;
use Str;
use Storage;
use Carbon\Carbon;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('user_network')->get();
        $users = $users->map(function($user){
            $fb = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 1)->first();
            $li = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 2)->first();
            $tw = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 3)->first();
            $total = DB::table('bubble_users_total_scores')->where('user_id', $user->id)->first();
            $user->score_fb = isset($fb) ? $fb->score : 0;
            $user->score_li = isset($in) ? $li->score : 0;
            $user->score_tw = isset($tw) ? $tw->score : 0;
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
    public function user(Request $request)
    {
        $us = $request->user();
        $user = User::with(['totalScore', 'user_network.network'])->where('uuid', $us->uuid)->first();
        $user->totalScore = $user->totalScore['score'];
        $user->percentScore = ceil(($user->totalScore * 100) / 1000);
        $user->user_network = $user->user_network->map(function($net){
            $net->score = Score::where('user_id', $net->user_id)->where('network_id', $net->network_id)->first();
            return $net;
        });
        return response()->json($user);
    }

    public function linked(Request $request){
        $us = $request->user();
        $linked = UserNetwork::with('network')->where('user_id', $us->id)->get();
        $keyed = $linked->keyBy('network_id');

        $all = $keyed->all();
        return response()->json($all);
    }

    public function links(Request $request){
        $user = $request->user();
        
        if($request->link == true){
           UserNetwork::create([
            'user_id' => $user->id,
            'network_id' => $request->type,
            'access_keys' => "{'userID' : ".$request->user['id'].",'accessKey' : ".$request->user['accessToken']."}",
            ]); 
       }else{
         UserNetwork::where('user_id', $user->id)->where('network_id', $request->type)->delete();
       }
       return response()->json(true);
        
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
        $user = User::where('email', $authUser->email)->first();

        $tokenResult = $user->createToken('Personal Access Token');
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

        return Response::json($token);
    }

    public function findOrCreateUser($user)
    {
        $authUser = User::where('email', $user->email)->first();
        if ($authUser) {
            return $user;
        }

        $user_insert = User::create([
            'name' => $user->name,
            'email' => $user->email,
            'profile' =>  $user->picture,
            'uuid' => Uuid::generate(),
            'location' => 'agadir',
            'locality' => 'agadir',

        ]);


        UserNetwork::create([
            'user_id' => $user_insert->id,
            'network_id' => $user->type_network,
            'access_keys' => "{'userID' : ".$user->id.",'accessKey' : ".$user->accessToken."}",
            ]);

        
        return $user_insert;
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

    public function uploadimage(Request $request){


         $image_64 = $request->file; //your base64 encoded data
         $uuid = $request->uuid;


        $pic = Storage::disk('public')->put('pictures', $image_64);

        $user = User::where('uuid', $uuid);
        $user->update([
            'profile' => $pic
        ]);

         return Response::json(Storage::url($pic));

    }
}
