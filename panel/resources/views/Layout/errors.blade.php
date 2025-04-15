@if ($errors->any())
    <div class="row justify-content-center">
        <div class="col-10">
            <div class="alert alert-danger px-1 my-2" role="alert">
                <ul class="m-0">
                    @foreach ($errors->all() as $error)
                        <li class="text-start">{{$error}}</li>
                    @endforeach    
                </ul>
            </div>
        </div>
    </div>
@endif