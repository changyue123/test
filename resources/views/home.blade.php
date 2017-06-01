@extends('layouts.app')
@section('addView')
    Hello world!
@endsection

@section('title')
    @parent
    新的标题||
@stop

@section('my')
    @parent
    扩展的内容
@stop

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Dashboard</div>

                <div class="panel-body">
                    You are logged in!
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
