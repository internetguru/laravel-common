<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="copyright" content="@lang('ig-common::layouts.provider.name')"/>
<meta name="author" content="@lang('ig-common::layouts.provider.email')"/>
<meta name="generator" content="{{ InternetGuru\LaravelCommon\Support\Helpers::getAppInfo() }}"/>
<meta name="supported-color-schemes" content="light">
<style>
    p, li {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
        margin: 1em 0;
        line-height: 1.4;
    }
    hr {
        margin: 1.5em 0;
        border: none;
        border-top: thin solid #ccc;
    }
    img {
        height: 1em;
        margin-right: 0.5em;
    }
    a {
        color: #3869d4;
    }
    a.button {
        -webkit-text-size-adjust: none;
        color: #fff;
        border-style: solid;
        border-color: #0d6efd;
        background-color: #0d6efd;
        box-sizing: border-box;
        position: relative;
        border-radius: 4px;
        display: inline-block;
        overflow: hidden;
        text-decoration: none;
        border-width: 0.5em 1em;
        -webkit-text-size-adjust: none;
    }
</style>
</head>
<body>
    <div style="max-width: 50em;">
        @yield('content')

        @section('footer')
            <x-ig-common::emails.footer-html />
        @show
    </div>
</body>
</html>
