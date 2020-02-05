<h1 style="text-align: center">Processing...</h1>

<script>
    var token = "{!! $token !!}";
    var status = "{!! $status !!}";

    window.opener.postMessage({token: token, status: status}, '*');
    window.close();
</script>