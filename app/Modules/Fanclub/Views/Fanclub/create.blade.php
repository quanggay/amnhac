@extends('backend.layouts.master')
@section ('scriptop')


@endsection
@section('content')

<div class = 'content'>
    <div class="intro-y flex items-center mt-8">
        <h2 class="text-lg font-medium mr-auto">
            Thêm câu lạc bộ
        </h2>
    </div>
    <div class="grid grid-cols-12 gap-12 mt-5">
        <div class="intro-y col-span-12 lg:col-span-12">
            <!-- BEGIN: Form Layout -->
            <form method="post" action="{{route('admin.Fanclub.store')}}" enctype="multipart/form-data">
                @csrf
                <div class="intro-y box p-5">
                    <div class="mt-3">
                        <label for="regular-form-1" class="form-label">Tên câu lạc bộ</label>
                        <input id="title" name="title" type="text" class="form-control" placeholder="Tên câu lạc bộ">
                 
                    </div>
                    <div class="mt-3">
                        <label for="" class="form-label">Photo</label>
                        <div class="px-4 pb-4 mt-5 flex items-center cursor-pointer relative">
                            <div data-single="true" id="mydropzone" class="dropzone" url="{{route('admin.upload.avatar')}}">
                                <div class="fallback"><input name="photo" type="file" multiple/></div>
                                <div class="dz-message" data-dz-message>
                                    <div class="font-medium">Kéo thả hoặc chọn ảnh.</div>
                                </div>
                            </div>
                            <input type="hidden" id="photo" name="photo"/>
                        </div>
                    </div>

                    <div class="mt-3">
                    <label for="" class="form-label">Photo</label>
                    <div class="px-4 pb-4 mt-5 flex items-center cursor-pointer relative">
                        <div data-single="true" id="mydropzone" class="dropzone" url="{{route('admin.upload.avatar')}}">
                            <div class="fallback"><input name="file" type="file" /></div>
                            <div class="dz-message" data-dz-message>
                                <div class="font-medium">Kéo thả hoặc chọn ảnh.</div>
                            </div>
                        </div>
                        <input type="hidden" id="photo" name="photo"/>
                    </div>
                </div>

                    <label for="regular-form-1" class="form-label">Mô tả</label>
                    <input id="summary" name="summary" type="text" class="form-control" placeholder="Mô tả">
                        
                        <label for="" class="form-label">Nội dung</label>
                       
                        <textarea class="editor"  id="editor1" name="content" >
                            {{old('content')}}
                        </textarea>
                    </div>


                    <div class="mt-3">
                        <div class="flex flex-col sm:flex-row items-center">
                            <label style="min-width:70px  " class="form-select-label"  for="status">Trạng thái</label>
                           
                            <select name="status" class="form-select mt-2 sm:mr-2"   >
                                <option value ="active" {{old('status')=='active'?'selected':''}}>Active</option>
                                <option value = "inactive" {{old('status')=='inactive'?'selected':''}}>Inactive</option>
                                
                            </select>
                        </div>
                    </div>
                     
                    <div class="mt-3">
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>    {{$error}} </li>
                                    @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                    <div class="text-right mt-5">
                        <button type="submit" class="btn btn-primary w-24">Lưu</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection

@section ('scripts')
<script>
    Dropzone.instances[0].options.multiple = false;
    Dropzone.instances[0].options.autoQueue = true;
    Dropzone.instances[0].options.maxFilesize = 2; // MB
    Dropzone.instances[0].options.maxFiles = 1;
    Dropzone.instances[0].options.dictDefaultMessage = 'Drop images anywhere to upload (1 image Max)';
    Dropzone.instances[0].options.acceptedFiles = "image/jpeg,image/png,image/gif";
    Dropzone.instances[0].options.addRemoveLinks = true;
    Dropzone.instances[0].options.headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};

    Dropzone.instances[0].on("success", function (file, response) {
        if(response.status == "true")
            $('#photo').val(response.link);
    });
    Dropzone.instances[0].on("removedfile", function (file) {
        $('#photo').val('');
    });
</script>

<script src="{{ asset('js/js/ckeditor.js') }}"></script>


<script src="{{asset('js/js/ckeditor.js')}}"></script>
<script>
     
        // CKSource.Editor
        ClassicEditor.create( document.querySelector( '#editor1' ), 
        {
            ckfinder: {
                uploadUrl: '{{route("admin.upload.ckeditor")."?_token=".csrf_token()}}'
                },
                mediaEmbed: {previewsInData: true}
            

        })

        .then( editor => {
            console.log( editor );
        })
        .catch( error => {
            console.error( error );
        })

</script>
@endsection