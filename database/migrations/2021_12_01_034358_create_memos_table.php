<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    // migraitonを実行したときの処理
    public function up()
    {
        Schema::create('memos', function (Blueprint $table) {
            //桁数の多い数値型のカラム
            //unsignedは符号がなくなって数値だけになる
            // 第二引数にtrueを入れておけば勝手にインクリメントしてくれる？
            $table->unsignedBigInteger('id',true);
            //文字型が多く入る型
            $table->longText('content');
            $table->unsignedBigInteger(('user_id'));
            //論理削除を定義-> deleted_atを自動生成
            //データベースには残っている
            $table->softDeletes();
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table-> timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            //user_idはusersテーブルに存在するidがないとだめという制約
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    // ロールバック
    public function down()
    {
        Schema::dropIfExists('memos');
    }
}
