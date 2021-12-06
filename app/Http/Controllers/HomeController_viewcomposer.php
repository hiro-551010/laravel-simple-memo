<!-- viewcomposerを使う前のhomecontroller -->

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\MemoTag;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //ここでメモを取得
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC')//ASC=昇順,DESC=降順
            ->get();
            
        $tags = Tag::where('user_id', "=", \Auth::id())->whereNull('deleted_at')->orderBy('updated_at', 'DESC')->get();

        // compact関数を使うと、viewsに値を渡せる
        return view('create', compact('memos', 'tags'));
    }

    public function store(Request $request)
    {
        // $requestにはPOST送信されたものが格納されている
        $posts = $request->all();
        // dd dump dieの略 → メソッドの引数のとった値を展開して止める → 値を確認
        // input等のname属性が確認できる
        // \Auth::id() は今現在のユーザーのidを持ってこれる
        // dd(\Auth::id());
        // dbに値を保存するときはinsert
        // Memo::insert(['content' => $posts['content'], 'user_id' => \Auth::id()]);
    
        // トランザクションはDBの処理がすべて完了したら、DBの変更を行う処理のこと
        // ===トランザクション開始 === 
        DB::transaction(function () use($posts) {
            // メモIDをインサートして取得
            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]);
            // 新規タグが入力されているかチェック
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists();
            // 新規タグが既にtagテーブルに存在していないかのチェック
            if(!empty($posts['new_tag']) && !$tag_exists ){
                // 新規タグが既に存在していなければ、tagsテーブルにインサート=>IDを所得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name'=> $posts['new_tag']]);
                // memo_tagsにインサートして、メモとタグを紐づける
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }
        
            // 既存タグが紐づけられた場合->memo_tagsにインサート
            if(!empty($posts['tags'][0])){
                foreach($posts['tags'] as $tag){
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
                }
            }
        });
        // ===トランザクションの処理終了 === 

        return redirect(route('home'));
    }

    public function edit($id)
    {
        $memos = Memo::select('memos.*')
            ->where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC')//ASC=昇順,DESC=降順
            ->get();

        // 主キーが$idと一致するものを取得する
        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
            ->leftjoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')
            ->leftjoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
            ->where('memos.user_id', '=', \Auth::id())
            ->where('memo_id', '=', $id)
            ->whereNull('memos.deleted_at')
            ->get();

        $include_tags = [];
        foreach($edit_memo as $memo){
            array_push($include_tags, $memo['tag_id']);
        }

        $tags = Tag::where('user_id', "=", \Auth::id())->whereNull('deleted_at')->orderBy('updated_at', 'DESC')->get();
        
        return view('edit', compact('memos', 'edit_memo', 'include_tags', 'tags'));
    }

    public function update(Request $request)
    {
        $posts = $request->all();

        // トランザクション開始
        DB::transaction(function () use($posts) {
            Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]);
            // 一旦メモとタグの紐づけを削除
            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();
            // 再度メモとタグの紐づけ
            foreach($posts['tags'] as $tag){
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag]);
            }
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists();
            if(!empty($posts['new_tag']) && !$tag_exists ){
                // 新規タグが既に存在していなければ、tagsテーブルにインサート=>IDを所得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name'=> $posts['new_tag']]);
                // memo_tagsにインサートして、メモとタグを紐づける
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);
            }
        });

        
        return redirect(route('home'));
    }

    public function destroy(Request $request)
    {
        $posts = $request->all();
        // これをすると、そのカラムごと消えてしまうのでNG
        // Memo::where('id', $posts['memo_id'])->delete()
        // deleted_atにtimestampを入力することで、削除されたとみなす
        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date('Y-m-d H:i:s', time())]);
        
        return redirect(route('home'));
    }
}
