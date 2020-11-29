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
use Geocoder;
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
            $user->total_score = !empty($total) ? $total->score : 0;
            $user->percentScore = ceil(($user->total_score * 100) / 1000);
            return $user;
        });

        $sorted = $users->sortByDesc('total_score');

        $users = $sorted->values()->all();
        return Response::json($users);
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
        return Response::json($user);
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
            'userId' => $request->user['id'],
            'access_keys' => "{'userID' : ".$request->user['id'].",'accessKey' : ".$request->user['accessToken']."}",
            ]); 
       }else{
         UserNetwork::where('user_id', $user->id)->where('network_id', $request->type)->delete();
       }
       return response()->json(true);
        
    }

    public function rankingLocal(Request $request){
        $us = $request->user();
        $user = User::find($us->id);
        $latitude = $user->latitude;
        $longtitude = $user->longtitude;
        $distance = 500;
        $users = User::with(['user_network'])
        ->whereRaw(DB::raw("latitude !='' and longtitude !=''
                                and ( 3959 * acos( cos( radians('$latitude') ) * cos( radians( latitude ) )
                                * cos( radians( longtitude ) - radians('$longtitude') ) + sin( radians('$latitude') )
                                * sin( radians( latitude ) ) ) ) < '$distance' "))->get();
        $users = $users->map(function($user){
            $fb = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 1)->first();
            $li = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 2)->first();
            $tw = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 3)->first();
            $total = DB::table('bubble_users_total_scores')->where('user_id', $user->id)->first();
            $user->score_fb = isset($fb) ? $fb->score : 0;
            $user->score_li = isset($in) ? $li->score : 0;
            $user->score_tw = isset($tw) ? $tw->score : 0;
            $user->total_score = !empty($total) ? $total->score : 0;
            $user->percentScore = ceil(($user->total_score * 100) / 1000);
            return $user;
        });
        $sorted = $users->sortByDesc('total_score');

        $users = $sorted->values()->all();
        return Response::json($users);
    }


    public function rankingGlobal(){
        $users = User::with(['user_network'])->get()->take(50);
        $users = $users->map(function($user){
            $fb = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 1)->first();
            $li = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 2)->first();
            $tw = DB::table('bubble_users_networks_scores')->where('user_id', $user->id)->where('network_id', 3)->first();
            $total = DB::table('bubble_users_total_scores')->where('user_id', $user->id)->first();
            $user->score_fb = isset($fb) ? $fb->score : 0;
            $user->score_li = isset($in) ? $li->score : 0;
            $user->score_tw = isset($tw) ? $tw->score : 0;
            $user->total_score = !empty($total) ? $total->score : 0;
            $user->percentScore = ceil(($user->total_score * 100) / 1000);
            return $user;
        });
        $sorted = $users->sortByDesc('total_score');

        $users = $sorted->values()->all();
        return Response::json($users);
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
        $authUser = User::with('user_network')
                    ->whereHas('user_network', function ($query) use($user) {
                        return $query->where('userId', '=', $user->id);
                    })
                    ->orWhere('email', $user->email)->first();
        if ($authUser) {
            return $authUser;
        }


        $user_insert = User::create([
            'name' => $user->name,
            'email' => $user->email,
            'profile' =>  $user->picture,
            'uuid' => Uuid::generate(),
            'latitude' => isset($user->latitude) ? $user->latitude : '-33.91722',
            'longtitude' => isset($user->longtitude) ? $user->longtitude : '151.23064',
            'locality' => 'UK',

        ]);


        UserNetwork::create([
            'user_id' => $user_insert->id,
            'network_id' => $user->type_network,
            'access_keys' => "{'userID' : ".$user->id.",'accessKey' : ".$user->accessToken."}",
            'userId' => $user->id,
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
        $photo = env('IMAGE_PATH', 'https://abeille.app/bubble/public/').Storage::url($pic);
        $user->update([
            'profile' => $photo
        ]);

         return Response::json($photo);
    }
}
