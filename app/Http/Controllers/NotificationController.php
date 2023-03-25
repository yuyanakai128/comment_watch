<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{

    public const TOTAL_COUNT = 25;

    protected $results = [];
    protected $count = 1;
    protected $lower_price;
    protected $upper_price;
    protected $excluded_word;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $user = auth()->user();
        $notifications = Notification::where('user_id',auth()->user()->id)->get();

        return view('notifications.index',compact('notifications','user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $parents = DB::table('categories')->where('level',0)->get();
        return view('notifications.create',compact('parents'));
    }

    protected function getChildrens(Request $request) {
        $childrens = DB::table('categories')->where('parentId',$request->get('category_id'))->get();
        $str = '<select class="form-control" id="children" name="children"><option value="0">すべて</option>';
        foreach($childrens as $item) {
            $str .= '<option value="'.$item->id.'">'.$item->name.'</option>';
        }
        $str .= '</select>';
        return $str;
    }

    protected function getSubChildrens(Request $request) {
        $childrens = DB::table('categories')->where('parentId',$request->get('category_id'))->get();
        $str = '<select class="form-control" id="subchildren" name="subchildren"><option value="0">すべて</option>';
        foreach($childrens as $item) {
            $str .= '<option value="'.$item->id.'">'.$item->name.'</option>';
        }
        $str .= '</select>';
        return $str;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $category = $request->get('category');
        $children = $request->get('children');
        $subchildren = $request->get('subchildren');
        if($subchildren == null || $subchildren == 0) {
            if($children == null || $children == 0) {
                $category_id = $category;
            }else{
                $category_id = $children;
            }
        }else{
            $category_id = $subchildren;
        }
        $user = auth()->user();
        $notification = new Notification();
        $notification->fill([
            "user_id" => $user->id,
            "keyword" => $request->get('keyword'),
            "lower_price" => $request->get('lower_price'),
            "upper_price" => $request->get('upper_price'),
            "category_id" => $category_id
        ]);

        $notification->save();


        return redirect()->action([NotificationController::class, 'index'])->with(['system.message.info' => "保管されました。"]);;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $notification = Notification::where('id',$id)->first();

        $level = $notification->category->level;

        $parents = DB::table('categories')->where('level',0)->get();

        $data = [];
        if($level == 0) {
            $data['parent_id'] = $notification->category->id;
        }else if($level == 1) {
            $data['children_id'] = $notification->category->id;
            $data['parent_id'] = $notification->category->parent->id;
            $data['childrens'] = $notification->category->parent->childrens;
        }else if($level == 2) {
            $data['subchildren_id'] = $notification->category->id;
            $data['children_id'] = $notification->category->parent->id;
            $data['parent_id'] = $notification->category->parent->parent->id;

            $data['childrens'] = $notification->category->parent->parent->childrens;
            $data['subchildrens'] = $notification->category->parent->childrens;
        }

        return view('notifications.show',compact('notification','parents','data'));
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        $category = $request->get('category');
        $children = $request->get('children');
        $subchildren = $request->get('subchildren');
        if($subchildren == null || $subchildren == 0) {
            if($children == null || $children == 0) {
                $category_id = $category;
            }else{
                $category_id = $children;
            }
        }else{
            $category_id = $subchildren;
        }
        $user = auth()->user();
        Notification::where('id', $id)
            ->update([
                "keyword" => $request->get('keyword'),
                "lower_price" => $request->get('lower_price'),
                "upper_price" => $request->get('upper_price'),
                "category_id" => $category_id
            ]);


        return redirect()->action([NotificationController::class, 'index'])->with(['system.message.success' => "編集されました。"]);
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
        Notification::where('id',$id)->delete();
        return redirect()->action([NotificationController::class, 'index'])->with(['system.message.success' => "削除されました。"]);
    }
}
