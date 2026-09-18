<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Forex Academy</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 10px 40px rgba(0,0,0,.3); }
        h1 { margin-top: 0; font-size: 1.5rem; }
        label { display: block; margin-bottom: .35rem; font-size: .9rem; color: #94a3b8; }
        input { width: 100%; padding: .75rem; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #fff; margin-bottom: 1rem; box-sizing: border-box; }
        button { width: 100%; padding: .85rem; border: none; border-radius: 8px; background: #22c55e; color: #052e16; font-weight: 700; cursor: pointer; }
        .errors { background: #450a0a; color: #fecaca; padding: .75rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
        .hint { font-size: .85rem; color: #64748b; margin-bottom: 1.25rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Start Learning in Seconds</h1>
        <p class="hint">Enter your name and phone number (or email). No password needed — jump straight into Days 1–3 free.</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label for="name">Full Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>

            <label for="phone_number">Phone Number</label>
            <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" placeholder="256770000000">

            <label for="email">Email (optional)</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com">

            <button type="submit">Start Free — Go to Dashboard</button>
        </form>
    </div>
</body>
</html>
