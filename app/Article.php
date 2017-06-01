<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    //
    public function findById(){

        $article = Article::find(2);

        echo $article->title;
    }
    public function findByCondition(){

        $article = Article::where('title', '我是标题')->first();

        echo $article->id;

        $articles = Article::where('id', '>', 10)->where('id', '<', 20)->get();

        foreach ($articles as $article) {

            echo $article->title;

        }
    }
    public function finfAll(){
        $articles = Article::all(); // 此处得到的 $articles 是一个对象集合，可以在后面加上 '->toArray()' 变成多维数组。

        foreach ($articles as $article) {

            echo $article->title;

        }
        //查询出所有文章并循环打印出所有标题，按照 updated_at 倒序排序
        $articles = Article::where('id', '>', 10)->where('id', '<', 20)->orderBy('updated_at', 'desc')->get();

        foreach ($articles as $article) {

            echo $article->title;

        }
    }
}
