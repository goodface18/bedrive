@extends('emails.default.base')

@section('content')
    <div>Verify Your Email Address</div>
    <br>
    <div>Thank you for creating an account on {{ $siteName }}. Please click the link below to verify your email address:</div>
    <br>
    <a href="{{ url('secure/auth/email/confirm/' . $code) }}" target="_blank">Confirm Now</a>
@endsection