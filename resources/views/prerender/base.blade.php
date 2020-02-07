<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="google" content="notranslate">

        @foreach($meta->getAll() as $tag)
            @if ($tag['nodeName'] === 'meta')
                <meta {!!$meta->tagToString($tag)!!}>
            @elseif ($tag['nodeName'] === 'link')
                <link {!!$meta->tagToString($tag)!!}>
            @elseif ($tag['nodeName'] === 'title')
                <title>{{$tag['_text']}}</title>
            @endif
        @endforeach
    </head>

    <body>
        @yield('body')
    </body>
</html>
