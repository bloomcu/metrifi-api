<!DOCTYPE html>
<html>
<head>
    <title>Invitation email</title>
</head>
<div>
    <h1>You've been invited to join the {{ $invitation->organization->title }} team on {{ config('app.name') }}</h1>

    <a href="{{ $invitation->url() }}">Join now</a>

    <p>
        You were invited by: <br>
        {{ $invitation->user->name }} <br>
        {{ $invitation->user->email }}
    </p>
</div>
</html>
