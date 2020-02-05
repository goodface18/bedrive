<h1 style="text-align: center">Logging in</h1>

<script>
    var status = "{!! $status !!}";
    var data   = null;

    @if(isset($data) && $data)
        @if(json_decode($data))
            data = {!! $data !!};
        @else
            data = '{!! $data !!}';
        @endif
    @endif

    window.opener.postMessage({status: status, callbackData: data}, '*');
    window.close();
</script>