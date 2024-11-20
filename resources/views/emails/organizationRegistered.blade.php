<!DOCTYPE html>
<html>
<head>
    <title>Notification</title>
</head>
<div>
    <h1>{{ config('app.name') }} notification</h1>

    <p>An organization called <strong>{{ $organization->title }}</strong> ({{ $organization->domain }}) has just registered on {{ config('app.name') }}.</p>

    <p>The organization was created by <strong>{{ $user->name }}</strong> ({{ $user->email }})</p>
</div>
</html>
