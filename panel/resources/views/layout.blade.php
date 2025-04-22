
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @include('Layout/head')
    {{-- in head includes yield('link_by_page') --}}
    @yield('style_by_page')
<body>
    @include('Layout/aside')

    <div class="container-fluid p-0" id="inicio">
        @include('Layout/header')
        
        @yield('Content')
        
        @auth
            @include('user.edit')
            @include('job.create')
        @endauth
    </div>
    
    @include('Layout/script')
    
    @yield('script_by_page')
</body>
</html>