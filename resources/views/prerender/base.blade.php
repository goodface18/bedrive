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
        <script data-ad-client="ca-pub-7269954483216817" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    </head>

    <body>
        @yield('body')
    </body>
</html>
