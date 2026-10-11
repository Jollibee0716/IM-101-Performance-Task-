<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset password | Gym Portal</title>
<style>
:root{--text:#f5f1ea;--muted:#a8a095;--role-color:#ff9900;--ink:#1a1208}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:grid;place-items:center;padding:24px 16px;color:var(--text);font-family:Poppins,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:radial-gradient(60% 50% at 15% 10%,rgba(255,153,0,.22),transparent 70%),radial-gradient(50% 45% at 90% 85%,rgba(204,85,0,.22),transparent 70%),#0a0908}
.card{width:100%;max-width:460px;padding:40px 36px;background:rgba(20,17,14,.78);border:1px solid rgba(255,255,255,.08);border-radius:22px;box-shadow:0 24px 60px rgba(0,0,0,.55)}
h1{font-size:28px;margin-bottom:6px}
.sub{color:var(--muted);font-size:15px;margin-bottom:24px}
.field{margin-bottom:18px}
.field label{display:block;margin-bottom:7px;font-size:14px;font-weight:600}
.field input{width:100%;padding:14px 16px;background:rgba(255,255,255,.05);border:1.5px solid rgba(255,255,255,.12);border-radius:14px;font-size:16px;color:inherit}
.field input:focus{outline:none;border-color:var(--role-color)}
.btn{width:100%;padding:15px;border:0;border-radius:14px;background:var(--role-color);color:var(--ink);font-size:16px;font-weight:700;cursor:pointer}
.err{background:rgba(255,80,60,.15);color:#ff8a78;padding:10px 14px;border-radius:10px;margin-bottom:16px;font-size:14px}
.alt{margin-top:20px;text-align:center;font-size:14px}
.alt a{color:var(--role-color);text-decoration:none;font-weight:600}
</style>
</head>
<body>
<main class="card">
  <h1>Reset password</h1>
  <p class="sub">Choose a new password for your account.</p>

  @if ($errors->any())
    <div class="err">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">

    <div class="field">
      <label for="password">New password</label>
      <input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" required>
    </div>
    <div class="field">
      <label for="password_confirmation">Confirm new password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Repeat your password" required>
    </div>
    <button type="submit" class="btn">Reset password</button>
  </form>

  <p class="alt"><a href="{{ route('login') }}">Back to sign in</a></p>
</main>
</body>
</html>