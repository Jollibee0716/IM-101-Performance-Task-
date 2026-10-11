<h1>Admin Dashboard</h1>
<p>Welcome, {{ auth()->user()->name }}</p>

<form method="POST" action="logout">
    @csrf
    <button type="submit">Logout</button>
</form>