<html>
<head>
    <title>CLDStorage - Update</title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>

    <link rel="stylesheet" href="{{ asset('client/assets/css/update.css?v1') }}">
    <link href='https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,400italic' rel='stylesheet'
          type='text/css'>
</head>

<body id="install">

<div class="container cont-pad-bottom" id="content">

    <div class="row logo"><img class="img-responsive" src="{{ asset('client/assets/images/logo-dark.png')  }}" alt="logo"></div>

    @if (session('status'))
        <div id="compat-check" class="step-panel">
            <p>{{ session('status') }}</p>

            <p>Go back to the <a href="{{url('')}}">homepage</a>.</p>
        </div>
    @else
        <form id="compat-check" class="step-panel" action="{{ url('secure/update/run') }}" method="post">
            {{ csrf_field() }}

            <p>This might take several minutes, please don't close this browser tab while update is in progress.</p>

            <div class="center-buttons">
                <button class="primary" type="submit">Update Now</button>
            </div>
        </form>
    @endif
</div>
</body>
</html>
