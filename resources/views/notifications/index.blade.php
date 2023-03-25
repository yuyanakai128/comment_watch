@extends('layouts.master')

@section('content')
<div class="content-page">
    <div class="content">

        <!-- Start Content-->
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-12">
                    @foreach (['info', 'success', 'danger', 'warning'] as $msg)
                        @if (Session::has('system.message.' . $msg))
                            <div class="alert alert-primary alert-dismissible fade show" role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                {{ Session::get('system.message.' . $msg) }}
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box page-title-box-alt">
                        <h4 class="page-title">ウォッチ管理</h4>
                    </div>
                </div>
            </div>     
            <!-- end page title --> 

            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-body">
                            <div class="row table-responsive">
                                @if ($notifications->count() < 5)
                                <div class="button-list text-end">
                                    <a href="{{route('notification.create')}}" class="btn btn-sm btn-primary mr-3 mb-3 btn-done">新しい条件を作る</a>
                                </div>
                                @endif

                                <div class="button-list text-end mb-2">
                                    メール数<span class="text-danger"> {{$user->mailSent}}回</span><br>
                                    メール上限<span class="text-danger"> {{$user->mailLimit}}回</span>
                                </div>
                                
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>キーワード</th>
                                            <th>下限価格</th>
                                            <th>上限価格</th>
                                            <th>カテゴリー</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($notifications as $notification)
                                        <tr>
                                            <td>{{$notification->keyword}}</td>
                                            <td>{{$notification->lower_price}}</td>
                                            <td>{{$notification->upper_price}}</td>
                                            <td>
                                                @if ($notification->category->level == 0)
                                                {{$notification->category->name}}
                                                @elseif($notification->category->level == 1) 
                                                {{$notification->category->parent->name}}<br>{{$notification->category->name}}
                                                @else
                                                {{$notification->category->parent->parent->name}}<br>{{$notification->category->parent->name}}<br>{{$notification->category->name}}
                                                @endif
                                            </td>  
                                            <td>
                                                <a href="{{ route('notification.show', $notification->id) }}" class="btn btn-sm btn-block btn-primary mt-1">編集</a>
                                                <form action="{{ route('notification.destroy',$notification)}}" method="post">
                                                    <input type="hidden" name="_method" value="delete" />
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <button type="submit" class="btn btn-sm btn-block mt-1 btn-danger btn-delete-company" data-id="{{ $notification->id }}">削除</button>
                                                </form>
                                                
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7"><h4 class="text-center mt-3">一致するレコードがありません</h4></td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>

                            </div>  <!-- end row -->
                        </div> <!-- end card body-->
                    </div> <!-- end card -->

                    <!-- end modal-->
                </div>
                <!-- end col-12 -->
            </div> <!-- end row -->
            
        </div> <!-- container-fluid -->

    </div> <!-- content -->

</div>
@endsection
