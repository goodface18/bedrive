@extends('common::framework')

@section('progressive-app-tags')
	<link rel="manifest" href="client/manifest.json">
	<meta name="theme-color" content="#1976d2">
	<script data-ad-client="ca-pub-7269954483216817" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
@endsection

@section('angular-styles')
    {{--angular styles begin--}}
		<link rel="stylesheet" href="client/styles.ab5b618ec8ffc5f3752c.css">
	{{--angular styles end--}}
@endsection

@section('angular-scripts')
    {{--angular scripts begin--}}
		<script type="text/javascript" src="client/runtime.553be139d36f038ae106.js"></script>
		<script type="text/javascript" src="client/polyfills.ed702c16ba28f9f1c20a.js"></script>
		<script type="text/javascript" src="client/main.e14285423dc9f681cfe5.js"></script>
	{{--angular scripts end--}}
@endsection

@section('before-loaded-content')
	<div class="global-spinner">
		<style>.global-spinner {display: none; align-items: center; justify-content: center; z-index: 999; background: #fff; position: fixed; top: 0; left: 0; width: 100%; height: 100%;}</style>
		<style>.la-ball-spin-clockwise,.la-ball-spin-clockwise>div{position:relative;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.la-ball-spin-clockwise{display:block;font-size:0;color:#1976d2}.la-ball-spin-clockwise.la-dark{color:#333}.la-ball-spin-clockwise>div{display:inline-block;float:none;background-color:currentColor;border:0 solid currentColor}.la-ball-spin-clockwise{width:32px;height:32px}.la-ball-spin-clockwise>div{position:absolute;top:50%;left:50%;width:8px;height:8px;margin-top:-4px;margin-left:-4px;border-radius:100%;-webkit-animation:ball-spin-clockwise 1s infinite ease-in-out;-moz-animation:ball-spin-clockwise 1s infinite ease-in-out;-o-animation:ball-spin-clockwise 1s infinite ease-in-out;animation:ball-spin-clockwise 1s infinite ease-in-out}.la-ball-spin-clockwise>div:nth-child(1){top:5%;left:50%;-webkit-animation-delay:-.875s;-moz-animation-delay:-.875s;-o-animation-delay:-.875s;animation-delay:-.875s}.la-ball-spin-clockwise>div:nth-child(2){top:18.1801948466%;left:81.8198051534%;-webkit-animation-delay:-.75s;-moz-animation-delay:-.75s;-o-animation-delay:-.75s;animation-delay:-.75s}.la-ball-spin-clockwise>div:nth-child(3){top:50%;left:95%;-webkit-animation-delay:-.625s;-moz-animation-delay:-.625s;-o-animation-delay:-.625s;animation-delay:-.625s}.la-ball-spin-clockwise>div:nth-child(4){top:81.8198051534%;left:81.8198051534%;-webkit-animation-delay:-.5s;-moz-animation-delay:-.5s;-o-animation-delay:-.5s;animation-delay:-.5s}.la-ball-spin-clockwise>div:nth-child(5){top:94.9999999966%;left:50.0000000005%;-webkit-animation-delay:-.375s;-moz-animation-delay:-.375s;-o-animation-delay:-.375s;animation-delay:-.375s}.la-ball-spin-clockwise>div:nth-child(6){top:81.8198046966%;left:18.1801949248%;-webkit-animation-delay:-.25s;-moz-animation-delay:-.25s;-o-animation-delay:-.25s;animation-delay:-.25s}.la-ball-spin-clockwise>div:nth-child(7){top:49.9999750815%;left:5.0000051215%;-webkit-animation-delay:-.125s;-moz-animation-delay:-.125s;-o-animation-delay:-.125s;animation-delay:-.125s}.la-ball-spin-clockwise>div:nth-child(8){top:18.179464974%;left:18.1803700518%;-webkit-animation-delay:0s;-moz-animation-delay:0s;-o-animation-delay:0s;animation-delay:0s}.la-ball-spin-clockwise.la-sm{width:16px;height:16px}.la-ball-spin-clockwise.la-sm>div{width:4px;height:4px;margin-top:-2px;margin-left:-2px}.la-ball-spin-clockwise.la-2x{width:64px;height:64px}.la-ball-spin-clockwise.la-2x>div{width:16px;height:16px;margin-top:-8px;margin-left:-8px}.la-ball-spin-clockwise.la-3x{width:96px;height:96px}.la-ball-spin-clockwise.la-3x>div{width:24px;height:24px;margin-top:-12px;margin-left:-12px}@-webkit-keyframes ball-spin-clockwise{0%,100%{opacity:1;-webkit-transform:scale(1);transform:scale(1)}20%{opacity:1}80%{opacity:0;-webkit-transform:scale(0);transform:scale(0)}}@-moz-keyframes ball-spin-clockwise{0%,100%{opacity:1;-moz-transform:scale(1);transform:scale(1)}20%{opacity:1}80%{opacity:0;-moz-transform:scale(0);transform:scale(0)}}@-o-keyframes ball-spin-clockwise{0%,100%{opacity:1;-o-transform:scale(1);transform:scale(1)}20%{opacity:1}80%{opacity:0;-o-transform:scale(0);transform:scale(0)}}@keyframes ball-spin-clockwise{0%,100%{opacity:1;-webkit-transform:scale(1);-moz-transform:scale(1);-o-transform:scale(1);transform:scale(1)}20%{opacity:1}80%{opacity:0;-webkit-transform:scale(0);-moz-transform:scale(0);-o-transform:scale(0);transform:scale(0)}}</style>
		<div class="la-ball-spin-clockwise la-2x">
			<div></div>
			<div></div>
			<div></div>
			<div></div>
			<div></div>
			<div></div>
			<div></div>
			<div></div>
		</div>
	</div>
	<script>
		setTimeout(function() {
		    var spinner = document.querySelector('.global-spinner');
            if (spinner) spinner.style.display = 'flex';
        }, 50);
	</script>
@endsection
