<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

    public function getMyMemo(){
        $query_tag = \Request::query('tag');

        // ベースのメソッド
        $query = Memo::query()->select('memos.*')
            ->where('user_id', '=', \Auth::id())
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'DESC');
        // ベースのメソッドここまで

        // もしクエリパラメーターtagがあれば
        if(!empty($query_tag)){
            // タグで絞込
            $memos = Memo::select('memos.*')
                ->leftjoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')
                ->where('memo_tags.tag_id', '=', $query_tag)
                ->where('user_id', '=', \Auth::id())
                ->whereNull('deleted_at')
                ->orderBy('updated_at', 'DESC')
                ->get();
        } else {
            // タグがなければ全て取得
            $memos = Memo::select('memos.*')
                ->where('user_id', '=', \Auth::id())
                ->whereNull('deleted_at')
                ->orderBy('updated_at', 'DESC')
                ->get();
        }

        if(!empty($query_tag)){
            $query->leftjoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')->where('memo_tags.tag_id', '=', $query_tag);
        }
        
        $memos = $query->get();
        return $memos;
    }
}
