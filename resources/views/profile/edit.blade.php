<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile — Forex Academy</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0b1120; color: #e2e8f0; margin: 0; line-height: 1.5; }
        header { background: #111827; border-bottom: 1px solid #1f2937; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; }
        header .brand { font-weight: 700; font-size: 1.1rem; color: #e2e8f0; text-decoration: none; }
        .header-actions { display: flex; align-items: center; gap: 1rem; font-size: .9rem; }
        .nav-link { color: #94a3b8; text-decoration: none; }
        .nav-link:hover { color: #fff; }
        button.link { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: inherit; }
        button.link:hover { color: #fff; }
        main { max-width: 520px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        h1 { margin: 0 0 .35rem; font-size: 1.75rem; }
        .subtitle { color: #94a3b8; margin: 0 0 1.75rem; font-size: .95rem; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 1.5rem; }
        label { display: block; margin-bottom: .35rem; font-size: .88rem; color: #94a3b8; font-weight: 500; }
        input { width: 100%; padding: .75rem .85rem; border-radius: 8px; border: 1px solid #334155; background: #0b1120; color: #fff; font-size: .95rem; margin-bottom: 1.1rem; }
        input:focus { outline: none; border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34,197,94,.15); }
        input.is-invalid { border-color: #ef4444; }
        .field-error { color: #f87171; font-size: .8rem; margin: -.75rem 0 1rem; }
        .alert { padding: .85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .9rem; }
        .alert.success { background: #14532d; color: #bbf7d0; border: 1px solid #166534; }
        .alert.error { background: #450a0a; color: #fecaca; border: 1px solid #7f1d1d; }
        .actions { display: flex; gap: .75rem; flex-wrap: wrap; margin-top: .5rem; }
        .btn { display: inline-block; padding: .75rem 1.35rem; border-radius: 8px; font-weight: 600; font-size: .9rem; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #22c55e; color: #052e16; }
        .btn-primary:hover { background: #16a34a; }
        .btn-outline { background: transparent; border: 1px solid #334155; color: #94a3b8; }
        .btn-outline:hover { border-color: #64748b; color: #fff; }
        @media (max-width: 480px) {
            .actions { flex-direction: column; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <header>
        <a href="{{ route('dashboard') }}" class="brand">Forex Academy</a>
        <div class="header-actions">
            <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button class="link" type="submit">Logout</button>
            </form>
        </div>
    </header>

    <main>
        <h1>Edit Profile</h1>
        <p class="subtitle">Update your name, phone number, and email.</p>

        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <label for="name">Full Name</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="@error('name') is-invalid @enderror"
                >
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                <label for="phone_number">Phone Number</label>
                <input
                    id="phone_number"
                    name="phone_number"
                    type="tel"
                    value="{{ old('phone_number', $user->phone_number) }}"
                    required
                    placeholder="256770000000"
                    class="@error('phone_number') is-invalid @enderror"
                >
                @error('phone_number')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                <label for="email">Email (optional)</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    placeholder="you@example.com"
                    class="@error('email') is-invalid @enderror"
                >
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                <div class="actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
