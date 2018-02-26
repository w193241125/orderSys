<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FoodController extends Controller
{
    public function __construct(Request $request)
    {
        //dd($request->user());
        $this->middleware('auth');
    }
    public function show(Request $request)
    {
        if ($request->sid){
            $sid = ['s.sid'=>$request->sid];
            $food = DB::table('foods as f')->leftJoin('shops as s','s.sid','=','f.sid')->where($sid)->orderBy('price','desc')->get();
        }else{
            $food = DB::table('foods as f')->leftJoin('shops as s','s.sid','=','f.sid')->orderBy('price','desc')->get();
        }
        $where[] = ['state','!=',3];
        if(Auth::user()->state==4){
            $where[] = ['sname','=',"Auth::user()->realname"];
        }
        $shop = DB::table('shops')->where($where)->get();

        return view('admin.food.food', ['food'=>$food,'shop'=>$shop]);
    }

    public function add()
    {
        if(Auth::user()->state==4){
            $where['sname'] = Auth::user()->realname;
        }
        $shop = DB::table('shops')->where('state','!=',3)->where($where)->get();
        return view('admin.food.add',['shop'=>$shop]);
    }

    public function doadd(Request $request)
    {
        $food['fname'] = $request->fname;
        $food['price'] = $request->price;
        $food['state'] = $request->state;
        $food['sid'] = $request->sid;

        $res = DB::table('foods')->insert($food);
        if ($res){
            return redirect('admin/food')->with(['foodMsg'=>'1']);
        } else {
            return redirect('admin/food')->with(['foodMsg'=>'2']);
        }
    }

    public function edit(Request $request)
    {
        $fid = $request->route('fid');
        $food = DB::table('foods')->where('fid','=',$fid)->get()->toArray();

        $where[] = ['state','!=',3];
        if(Auth::user()->state==4){
            $where[] = ['sname','=',Auth::user()->realname];
        }
        $shop = DB::table('shops')->where($where)->get()->toArray();
        return view('admin.food.edit',['food'=>$food[0],'shop'=>$shop]);
    }

    public function doedit(Request $request)
    {
        //dd($request);
        $fid= $request->fid;
        $data['fname'] = $request->fname;
        $data['sid'] = $request->sid;
        $data['state'] = $request->state;
        $data['price'] = $request->price;
        $res = DB::table('foods')->where('fid','=',$fid)->update($data);
        if ($res){
            return redirect('admin/food')->with(['foodMes'=>1]);
        }else{
            return redirect('admin/food')->with(['foodMes'=>2]);
        }

    }
}
