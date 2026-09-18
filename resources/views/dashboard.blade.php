<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Dashboard — Forex Academy</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }
        header { background: #1e293b; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }
        .badge { display: inline-block; padding: .25rem .6rem; border-radius: 999px; font-size: .75rem; background: #334155; }
        .badge.free { background: #14532d; color: #bbf7d0; }
        .badge.paid { background: #1e3a8a; color: #bfdbfe; }
        .module { background: #1e293b; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: .75rem; display: flex; justify-content: space-between; align-items: center; }
        .module.locked { opacity: .45; }
        .module h3 { margin: 0 0 .25rem; font-size: 1rem; }
        .module p { margin: 0; font-size: .85rem; color: #94a3b8; }
        a.btn { color: #22c55e; text-decoration: none; font-weight: 600; }
        form.logout { display: inline; }
        button.link { background: none; border: none; color: #94a3b8; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <div>
            <strong>Forex Academy</strong>
            <span class="badge {{ $user->subscription_status === 'free' ? 'free' : 'paid' }}">
                {{ str_replace('_', ' ', ucfirst($user->subscription_status)) }}
            </span>
        </div>
        <div>
            {{ $user->name }}
            <form class="logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="link" type="submit">Logout</button>
            </form>
        </div>
    </header>

    <main>
        <h1>Your 30-Day Course</h1>
        <p style="color:#94a3b8;">Days 1–3 are unlocked. Upgrade to access the full curriculum.</p>

        @foreach ($modules as $module)
            @php $accessible = $accessibleModules->contains('id', $module->id); @endphp
            <div class="module {{ $accessible ? '' : 'locked' }}">
                <div>
                    <h3>Day {{ $module->day_number }}: {{ $module->title }}</h3>
                    <p>
                        @if ($module->is_free)
                            Free preview
                        @elseif ($accessible)
                            Unlocked
                        @else
                            Locked — upgrade to continue
                        @endif
                        @if ($progress->get($module->id))
                            · Completed
                        @endif
                    </p>
                </div>
                @if ($accessible)
                    <span class="btn">Open</span>
                @endif
            </div>
        @endforeach
    </main>
</body>
</html>
