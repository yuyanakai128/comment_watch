@extends('layouts.master')

@section('content')
<div class="content-page">
    <div class="content">

        <!-- Start Content-->
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box page-title-box-alt">
                        <h4 class="page-title">アラート条件</h4>
                    </div>
                </div>
            </div>     
            <!-- end page title --> 

            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-12">
                                    <div class="p-2">
                                        <form method="POST" action="{{route('notification.update',$notification->id)}}" class="form-horizontal" role="form" id="storeForm">
                                            @csrf
                                            <input type="hidden" name="_method" value="PUT">
                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="keyword">キーワード</label>
                                                <div class="col-md-10">
                                                    <input type="text" name="keyword" id="keyword" class="form-control" value="{{$notification->keyword}}" required>
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="lower_price">下限価格</label>
                                                <div class="col-md-10">
                                                    <input class="form-control" type="number" id="lower_price" value="{{$notification->lower_price}}" name="lower_price">
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="upper_price">上限価格</label>
                                                <div class="col-md-10">
                                                    <input class="form-control" type="number" id="upper_price" value="{{$notification->upper_price}}" name="upper_price">
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="category">カテゴリー</label>
                                                <div class="col-md-10" id="category_tab">
                                                    <select class="form-control" id="category"  name="category">
                                                        <option value="0">すべて</option>
                                                        @foreach ($parents as $item)
                                                            @if (isset($data['parent_id']) && ($data['parent_id'] == $item->id))
                                                                <option value="{{$item->id}}" selected>{{$item->name}}</option>
                                                            @else 
                                                                <option value="{{$item->id}}">{{$item->name}}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label"></label>
                                                <div class="col-md-10" id="children_tab" >
                                                    @if (isset($data['childrens']))
                                                    <select class="form-control" id="children"  name="children">
                                                        <option value="0">すべて</option>
                                                        @foreach ($data['childrens'] as $item)
                                                            @if (isset($data['children_id']) && ($data['children_id'] == $item->id))
                                                                <option value="{{$item->id}}" selected>{{$item->name}}</option>
                                                            @else 
                                                                <option value="{{$item->id}}">{{$item->name}}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label"></label>
                                                <div class="col-md-10" id="subchildren_tab">
                                                    @if (isset($data['subchildrens']))
                                                    <select class="form-control" id="subchildren"  name="subchildren">
                                                        <option value="0">すべて</option>
                                                        @foreach ($data['subchildrens'] as $item)
                                                            @if (isset($data['subchildren_id']) && ($data['subchildren_id'] == $item->id))
                                                                <option value="{{$item->id}}" selected>{{$item->name}}</option>
                                                            @else 
                                                                <option value="{{$item->id}}">{{$item->name}}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @endif
                                                </div>
                                            </div>
                                     
                                            <div class="mb-2 row">
                                                <div class="button-list text-end">
                                                    <input type="submit" value="編集する" class="btn btn-primary pl-4 pr-4 mr-3">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                            <!-- end row -->
                        </div>
                    </div> <!-- end card -->

                    <!-- end modal-->
                </div>
                <!-- end col-12 -->
            </div> <!-- end row -->
            
        </div> <!-- container-fluid -->

    </div> <!-- content -->

</div>
@endsection

@section('scripts')
<script>
    $( document ).ready(function() {
        $('#category').on('change',function() {
            $('#children_tab').children().remove();
            $('#subchildren_tab').children().remove();
            var category_id = $(this).find('option').filter(':selected').val();
            $.ajax({
                type:'POST',
                url:"{{ route('getChildrens') }}",
                data:{
                    category_id:category_id,
                    _token: '{{csrf_token()}}'},
                success:function(results){
                    $('#children_tab').append(results);
                        
                }
            });
            
        })
        $('#children_tab').on('change',function() {
            $('#subchildren_tab').children().remove();
            var category_id = $(this).find('option').filter(':selected').val();
            $.ajax({
                type:'POST',
                url:"{{ route('getSubChildrens') }}",
                data:{
                    category_id:category_id,
                    _token: '{{csrf_token()}}'},
                success:function(results){
                    $('#subchildren_tab').append(results);
                        
                }
            });
            
        })


    });
</script>
@endsection