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
                                        <form method="POST" action="{{route('notification.store')}}" class="form-horizontal" role="form" id="storeForm">
                                            @csrf
                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="keyword">検索キーワード<span class="text-danger">*</span></label>
                                                <div class="col-md-10">
                                                    <input type="text" name="keyword" id="keyword" class="form-control" value="" placeholder="スペースで区切ります。" required>
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="lower_price">下限価格</label>
                                                <div class="col-md-10">
                                                    <input class="form-control" type="number" id="lower_price" name="lower_price">
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="upper_price">上限価格</label>
                                                <div class="col-md-10">
                                                    <input class="form-control" type="number" id="upper_price" name="upper_price">
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label" for="category">カテゴリー</label>
                                                <div class="col-md-10" id="category_tab">
                                                    <select class="form-control" id="category"  name="category">
                                                        <option value="0">すべて</option>
                                                        @foreach ($parents as $item)
                                                            <option value="{{$item->id}}">{{$item->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label"></label>
                                                <div class="col-md-10" id="children_tab" >
                                                    
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <label class="col-md-2 col-form-label"></label>
                                                <div class="col-md-10" id="subchildren_tab">
                                                    
                                                </div>
                                            </div>

                                            <div class="mb-2 row">
                                                <div class="button-list text-end">
                                                    <input type="submit" value="監視条件を保管する" class="btn btn-primary pl-4 pr-4 mr-3">
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