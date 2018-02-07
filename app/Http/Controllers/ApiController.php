<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Vip;
use App\history;
use Carbon\Carbon;
use App\User;
use App\transaction;
use App\Config;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function me(){
        return response()->json([
            'name'=>auth::user()->name,
            'fbid'=>auth::user()->fbid,
            'email'=>auth::user()->email,
            'money'=>number_format(auth::user()->money).' VNĐ',
            'level'=>auth::user()->level,
            'status'=>auth::user()->status,
            'avatar'=>auth::user()->avatar
        ],200);
    }
    public function infouser(){
        $user = User::where('id',Input::get('id'))->first();
        return response()->json([
            'name'=>$user->name,
            'fbid'=>$user->fbid,
            'email'=>$user->email,
            'money'=>number_format($user->money).' VNĐ',
            'level'=>$user->level,
            'status'=>$user->status,
            'avatar'=>$user->avatar
        ],200);
    }
    public function alluser(){
        $his = User::orderBy('id','ASC')->orderBy('level', 'DESC')->paginate(25);
        return \response()->json($his);   
    }
    
    public function searchuser(){
        $his = User::where('name','LIKE','%'.Input::get('key').'%')->orWhere('email','LIKE','%'.Input::get('key').'%')->orWhere('fbid','LIKE','%'.Input::get('key').'%')->get();
        return \response()->json($his);   
    }
    public function history(){
        $his = history::where('me','0')->orderBy('updated_at','DESC')->get();
        return \response()->json($his);
    }
    public function transaction(){
        $transaction = transaction::select(['transaction.userid','transaction.money','transaction.created_at','transaction.id','transaction.status','transaction.transactionid','users.name'])->join('users', 'users.id', '=', 'transaction.userid')->get();
        return $transaction;
    }
    public function ConfirmTransaction(Request $request){
        if(auth::user()->email == 'builuc1998@gmail.com' || auth::user()->email == 'vinguyet6666@asiamovie.info' || auth::user()->level == 3){
            $transaction = transaction::select(['userid','money','status'])->where('id',$request->id)->first();
            if($transaction && $transaction->status != 'done'){
                transaction::where('id',$request->id)->update(['status'=>'done','admin'=>auth::user()->id,'updated_at'=>date('Y-m-d H:i:s',time())]);
                User::where('id',$transaction->userid)->update(['money'=>DB::raw('money +'.$transaction->money)]);   
            }else{
                return response()->json(['success'=>'false','message'=>'Giao dịch không tồn tại hoặc đã được xử lý!']);
            }
            return response()->json(['success'=>'true','message'=>'Xác nhận giao dịch thành công!']);
        }else{
            return response()->json(['success'=>'false','message'=>'Bạn không có quyền thực hiện hành động này!']);
        }
    }
    public function loadConfig(){
        $config = Config::select(['key','value','link'])->get();
        return $config;
    }
    public function changeConfig(Request $request){
        $val = $request->all();
        foreach($val as $key=>$v){
            if($key == 'link-powered'){
                Config::where('key','powered')->update(['link'=>$v]);
            }else{
                Config::where('key',$key)->update(['value'=>$v]);                
            }
        }
        return response()->json(['success'=>'true','message'=>'Thay đổi thành công']);
    }
    public function changeChucvu(Request $request){
        $level = $request->chucvu;
        $id = $request->id;
        if(auth::user()->email == 'builuc1998@gmail.com' || auth::user()->email == 'vinguyet6666@asiamovie.info' || auth::user()->level == 3){
            User::where('id',$id)->update(['level'=>$level]);
            return response()->json(['message'=>'Cập nhật thành công']);
        }else{
            return response()->json(['message'=>'Bạn không có quyền để thay đổi'],404);
        }
    }
}
