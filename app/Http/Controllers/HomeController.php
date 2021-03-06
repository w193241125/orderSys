<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{

    protected $day_type = [
            1=>['A','B','C'],
            2=>['D','E','F'],
            3=>['G','H','I'],
            4=>['J','K','L'],
            5=>['M','N','O'],
            6=>['P','Q','R'],
            7=>['S','T','U'],
        ];
    protected $time_type = [
        'A'=>1,'D'=>1,'G'=>1,'J'=>1,'M'=>1,'P'=>1,'S'=>1,
        'B'=>2,'E'=>2,'H'=>2,'K'=>2,'N'=>2,'Q'=>2,'T'=>2,
        'C'=>3,'F'=>3,'I'=>3,'L'=>3,'O'=>3,'R'=>3,'U'=>3,
    ];
    protected $week_type = [
        'A'=>1,'D'=>2,'G'=>3,'J'=>4,'M'=>5,'P'=>6,'S'=>0,
        'B'=>1,'E'=>2,'H'=>3,'K'=>4,'N'=>5,'Q'=>6,'T'=>0,
        'C'=>1,'F'=>2,'I'=>3,'L'=>4,'O'=>5,'R'=>6,'U'=>0,
    ];
    protected $week_name = [
        'A'=>'星期一早上','D'=>'星期二早上','G'=>'星期三早上','J'=>'星期四早上','M'=>'星期五早上','P'=>'星期六早上','S'=>'星期天早上',
        'B'=>'星期一中午','E'=>'星期二中午','H'=>'星期三中午','K'=>'星期四中午','N'=>'星期五中午','Q'=>'星期六中午','T'=>'星期天中午',
        'C'=>'星期一晚上','F'=>'星期二晚上','I'=>'星期三晚上','L'=>'星期四晚上','O'=>'星期五晚上','R'=>'星期六晚上','U'=>'星期天晚上',
    ];
    protected $money_limited = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $weekOfYear = date('W',time());//获取本周是今年的第几周, 每周从周一开始,
        //dd(Carbon::parse(date('Y-m-d',time())));
        $fmods = fmod($weekOfYear,2);
        $dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几
        $food = DB::table('foods')->orderBy('price','desc')->where('fstate','=',1)->get();
        $shop = DB::table('shops')->where('state','!=',2)->get();
        $menu = DB::table('menus')->where(['mweek'=>1,'mstate'=>1])->get()->toArray();
        $time_limited = DB::table('time_limited')->get()->toArray();

        foreach ($menu as &$v) {
            $v->food = explode(',',trim($v->fid,','));
        }
        if ($fmods==1){
            $limit = 18;
        }else{
            $limit = 15;//周日点餐需要修改这limit,为21
        }
        $type = DB::table('types')->limit($limit)->get();

        return view('home', ['menu' => $menu, 'food' => $food, 'shop' => $shop, 'dayWeek' => $dayWeek,'type'=>$type,'fmods'=>$fmods,'timelimited'=>$time_limited]);
    }

    public function getAddDays()
    {

    }
    public function upd(Request $request)
    {
        if(Auth::user()->state==2){die('您已被禁止访问,请联系管理员~');}
        $dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几

        //获取每天禁止点餐时间
        $time_limited = DB::table('time_limited')->get()->toArray();
        $t_limit = [];
        foreach ($time_limited as $item) {
            $t_limit[$item->time_mark] = $item->time_limited;//  [1 => "07:00"]
        }

        //获取商家，并构建商家限额数组
        $sop = DB::table('shops')->where('state','!=',2)->get();
        foreach ($sop as $item) {
            $money_limit[$item->sid] = $item->limit_money;
        }

        $weekOfYear = date('W',time());
        $data['uid'] = Auth::user()->uid;
        $data['uname'] = Auth::user()->uname;
        $data['total'] = 0;
        $data['food'] = '';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['week_of_year'] = $weekOfYear;
        if (isset($request->shop)){
            foreach ($request->shop as $mark=>$shop) {
                if(Auth::user()->company !=1 && !in_array($mark,['A','D','G','J','M','P','S'])){ continue; }//非 350 用户只能点早餐
                if ($shop == 0){
                    DB::table('orders')->where(['uid'=>$data['uid'],'tmark'=>$mark,'week_of_year'=>$weekOfYear,'year'=>date('Y',time())])->update(['ostate'=>2,'delete_at'=>date('Y-m-d H:i:s',time())]);
                }
            }
        }

        if (isset($request->order)){
            $dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几
            $today = date('Y-m-d');//获取今天日期

            foreach ($request->order as $key=>$item) {
                $data['tmark'] = $key;
                if ($dayWeek ==1){
                    if ($key=='A'||$key=='B'||$key=='C'){$data['date']=$today;}
                    if ($key=='D'||$key=='E'||$key=='F'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                    if ($key=='G'||$key=='H'||$key=='I'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+4 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+5 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+6 day"));}
                }elseif($dayWeek ==2){
                    if ($key=='D'||$key=='E'||$key=='F'){$data['date']=$today;}
                    if ($key=='G'||$key=='H'||$key=='I'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+4 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+5 day"));}
                }elseif($dayWeek ==3){
                    if ($key=='G'||$key=='H'||$key=='I'){$data['date']=$today;}
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+4 day"));}
                }elseif($dayWeek ==4){
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=$today;}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                }elseif($dayWeek ==5){
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=$today;}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                }elseif($dayWeek ==6){
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=$today;}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                }elseif($dayWeek ==7 || $dayWeek==0){
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=$today;}
                }

                if (is_array($item)){
                    $m=0;
                    foreach ($item as $k=>$v) {
                        $data['sid'] = $k;
                        $drink = 0;
                        foreach ($v as $fid=>$price) {
                            $re = DB::table('foods')->where('fid','=',$fid)->get()->toArray();
                            if ($re[0]->ftype==3){
                                $drink ++;
                                if ($drink >1){
                                    return redirect('home/show')->with(['error_msg'=>$this->week_name[$data['tmark']].'开始点的餐都失败了哦，每餐不能点超过1个饮料']);
                                }
                            }
                            if ($m>0){
                                if ($sid != $re[0]->sid){
                                    return redirect('home/showNextWeek')->with(['error_msg'=>'同一餐不能同时点2家哦！']);
                                };
                            }
                            $data['food'] .= $re[0]->fname.'+';
                            $data['total'] += $price;
                        }
                        $m++;
                    }
                }
                // 判断点餐时间是否超时
                if ($dayWeek == $this->week_type[$data['tmark']]  &&  time()>strtotime($t_limit[$this->time_type[$data['tmark']]]) ){
                    return redirect('home/show')->with(['error_msg'=>'点餐失败，超过点餐时限']);
                }elseif($dayWeek > $this->week_type[$data['tmark']] && $this->week_type[$data['tmark']] != 0){//排除周日的
                    return redirect('home/show')->with(['error_msg'=>'点餐失败，不能点今天之前的餐']);
                }

                //判断点餐金额是否超额度 todo
                if ($money_limit[$data['sid']]<$data['total']){
                    return redirect('home/show')->with(['error_msg'=>'点餐失败，点餐金额超过限额']);
                }

                $data['food'] = trim($data['food'],'+');
                $data['year'] = date('Y',time());
                $res = DB::table('orders')->where(['tmark'=>$data['tmark'],'week_of_year'=>$weekOfYear,'uid'=>$data['uid'],'ostate'=>1, 'year'=>date('Y',time())])->get()->toArray();
                if ($res){
                    DB::table('orders')->where('oid','=',$res[0]->oid)->update($data);
                } else {
                    DB::table('orders')->insert($data);
                }
                $data['total'] = 0;
                $data['food'] = '';
            }
        }
        return redirect('home/show')->with(['message'=>'点餐成功']);
    }

    public function show()
    {
        $uid = Auth::user()->uid;
        //获取本周是今年第几周
        $weekOfYear = date('W',time());
        $year = date('Y',time());
        $order = DB::table('orders')->where(['week_of_year'=>$weekOfYear,'uid'=>$uid, 'year'=>$year])->where('ostate','=',1)->get()->toArray();
        $type = DB::table('types')->get()->toArray();
        return view('show',['order'=>$order,'type'=>$type]);
    }

    public function nextWeekIndex()
    {
        //$date = new \DateTime;
        //$weekOfYear = date_get_week_number($date) +1;
        $weekOfYear = date('W',time())+1;
        $fmods = fmod($weekOfYear,2);

        $dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几
        $food = DB::table('foods')->orderBy('price','desc')->where('fstate','=',1)->get();
        $shop = DB::table('shops')->where('state','!=',2)->get();
        $menu = DB::table('menus')->where(['mweek'=>2,'mstate'=>1])->get()->toArray();
        foreach ($menu as &$v) {
            $v->food = explode(',',trim($v->fid,','));
        }
        if ($fmods==1){
            $limit = 18;
        }else{
            $limit = 15;
        }
        $type = DB::table('types')->limit($limit)->get();

        return view('nextweek', ['menu' => $menu, 'food' => $food, 'shop' => $shop, 'dayWeek' => $dayWeek,'type'=>$type,'fmods'=>$fmods]);
    }

    public function updNextWeek(Request $request)
    {
        if(Auth::user()->state==2){die('您已被禁止访问,请联系管理员~');}
        $dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几
        if($dayWeek == 1 || $dayWeek==2 || $dayWeek==3 || $dayWeek==4){
            die('现在不能点下周的餐哦~');
        }
        $weekOfYear = date('W',time());
        $data['uid'] = Auth::user()->uid;
        $data['uname'] = Auth::user()->uname;
        $data['total'] = 0;
        $data['food'] = '';
        $data['week_of_year'] = $weekOfYear + 1;// 设置周数为下周*
        //if ($dayWeek==7 || $dayWeek==0){$data['week_of_year'] = $weekOfYear;}//周日是新一周的开始
        //判断是否取消订餐
        if (isset($request->shop)){
            foreach ($request->shop as $mark=>$shop) {
                if(Auth::user()->company !=1 && !in_array($mark,['A','D','G','J','M','P','S'])){ continue; }
                if ($shop == 0){
                    DB::table('orders')->where(['uid'=>$data['uid'],'tmark'=>$mark,'week_of_year'=>$data['week_of_year'],'year'=>date('Y',time())])->update(['ostate'=>2,'delete_at'=>date('Y-m-d H:i:s',time())]);
                }
            }
        }
        //获取商家，并构建商家限额数组
        $sop = DB::table('shops')->where('state','!=',2)->get();
        foreach ($sop as $item) {
            $money_limit[$item->sid] = $item->limit_money;
        }

        if (isset($request->order)){
            $dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几
            foreach ($request->order as $key=>$item) {
                $data['tmark'] = $key;
                if($dayWeek ==5){
                    if ($key=='A'||$key=='B'||$key=='C'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                    if ($key=='D'||$key=='E'||$key=='F'){$data['date']=date("Y-m-d",strtotime("+4 day"));}
                    if ($key=='G'||$key=='H'||$key=='I'){$data['date']=date("Y-m-d",strtotime("+5 day"));}
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=date("Y-m-d",strtotime("+6 day"));}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+7 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+8 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+9 day"));}
                }elseif($dayWeek ==6){
                    if ($key=='A'||$key=='B'||$key=='C'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                    if ($key=='D'||$key=='E'||$key=='F'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                    if ($key=='G'||$key=='H'||$key=='I'){$data['date']=date("Y-m-d",strtotime("+4 day"));}
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=date("Y-m-d",strtotime("+5 day"));}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+6 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+7 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+8 day"));}
                }elseif($dayWeek ==7 || $dayWeek==0){
                    if ($key=='A'||$key=='B'||$key=='C'){$data['date']=date("Y-m-d",strtotime("+1 day"));}
                    if ($key=='D'||$key=='E'||$key=='F'){$data['date']=date("Y-m-d",strtotime("+2 day"));}
                    if ($key=='G'||$key=='H'||$key=='I'){$data['date']=date("Y-m-d",strtotime("+3 day"));}
                    if ($key=='J'||$key=='K'||$key=='L'){$data['date']=date("Y-m-d",strtotime("+4 day"));}
                    if ($key=='M'||$key=='N'||$key=='O'){$data['date']=date("Y-m-d",strtotime("+5 day"));}
                    if ($key=='P'||$key=='Q'||$key=='R'){$data['date']=date("Y-m-d",strtotime("+6 day"));}
                    if ($key=='S'||$key=='T'||$key=='U'){$data['date']=date("Y-m-d",strtotime("+7 day"));}
                }

                if (is_array($item)){
                    $m = 0;
                    foreach ($item as $k=>$v) {
                        $data['sid'] = $k;
                        $drink = 0;

                        foreach ($v as $fid=>$price) {
                            $re = DB::table('foods')->where('fid','=',$fid)->get()->toArray();
                            //dd($re[0]->fname);
                            if ($re[0]->ftype==3){
                                $drink ++;
                                if ($drink >1){
                                    return redirect('home/showNextWeek')->with(['error_msg'=>$this->week_name[$data['tmark']].'开始点的餐都失败了哦，每餐不能点超过1个饮料']);
                                }
                            }
                            if ($m>0){
                                if ($sid != $re[0]->sid){
                                    return redirect('home/showNextWeek')->with(['error_msg'=>'同一餐不能同时点2家哦！']);
                                };
                            }
                            $sid = $re[0]->sid;
                            $data['food'] .= $re[0]->fname.'+';
                            $data['total'] += $price;
                        }
                        $m++;
                    }
                }
                //判断点餐金额是否超额度 todo
                if ($money_limit[$data['sid']]<$data['total']){
                    return redirect('home/showNextWeek')->with(['error_msg'=>'点餐失败，点餐金额超过限额']);
                }
                $data['food'] = trim($data['food'],'+');
                $data['year'] = date('Y',time());
                $res = DB::table('orders')->where(['tmark'=>$data['tmark'],'week_of_year'=>$data['week_of_year'],'uid'=>$data['uid'],'ostate'=>1,])->get()->toArray();
                if ($res){
                    DB::table('orders')->where('oid','=',$res[0]->oid)->update($data);
                } else {
                    $data['created_at'] = date('Y-m-d H:i:s',time());
                    DB::table('orders')->insert($data);
                }
                $data['total'] = 0;
                $data['food'] = '';
            }
        }
        return redirect('home/showNextWeek')->with(['message'=>'点餐成功']);
    }

    public function showNextWeek()
    {
        //$dayWeek = Carbon::parse(date('Y-m-d',time()))->dayOfWeek;//获取今天是周几
        $uid = Auth::user()->uid;
        //获取本周是今年第几周
        //$date = new \DateTime;
        //$weekOfYear = date_get_week_number($date) + 1;//结果加1 为下周
        $weekOfYear = date('W',time())+1;
        //if ($dayWeek==7 || $dayWeek === 0){$weekOfYear = date_get_week_number($date);}
        $fmods = fmod($weekOfYear,2);
        $year = date('Y',time());
        //$food = DB::table('foods')->select(['fid','fname'])->get()->toArray();
        $order = DB::table('orders')->where(['week_of_year'=>$weekOfYear,'uid'=>$uid, 'year'=>$year])->where('ostate','=',1)->get()->toArray();
        $type = DB::table('types')->get()->toArray();
        return view('showNextWeek',['order'=>$order,'type'=>$type,'fmods'=>$fmods]);
    }

    public function jishubu()
    {
        $uname = [18=>16, 19=>17, 20=>18,21=>19,22=>20,47=>41,93=>83,94=>84,95=>85,121=>89];
        $name = [18=>'何海平', 19=>'闵小明', 20=>'梁燕珊',21=>'刘冠生',22=>'杨南峰',47=>'樊君泽',93=>'郭志昊',94=>'吴顺',95=>'陈裕升',121=>'冼永豪'];
        $uid = [18,19,20,21,22,47,93,94,95,121];
        //获取本周是今年第几周
        $weekOfYear = date('W',time());
        $fmods = fmod($weekOfYear,2);
        $order = DB::table('orders')->where(['week_of_year'=>$weekOfYear,'year'=>date('Y',time())])->whereIn('uid',$uid)->where('ostate','=',1)->get()->toArray();
        $type = DB::table('types')->get()->toArray();
//        dd($order);
        return view('jishubu',['order'=>$order,'type'=>$type, 'name'=>$name, 'uname'=>$uname]);
    }
}
