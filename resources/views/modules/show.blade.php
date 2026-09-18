<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day {{ $module->day_number }}: {{ $module->title }} — Forex Academy</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; background: #fafafa; color: #1a1a2e; margin: 0; line-height: 1.7; }
        .topbar { background: #0b1120; color: #e2e8f0; padding: .85rem 1.5rem; display: flex; justify-content: space-between; align-items: center; font-family: system-ui, sans-serif; font-size: .88rem; }
        .topbar a { color: #94a3b8; text-decoration: none; }
        .topbar a:hover { color: #fff; }
        .article-wrap { max-width: 720px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
        .day-label { font-family: system-ui, sans-serif; font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #22c55e; margin-bottom: .5rem; }
        h1 { font-size: 2rem; margin: 0 0 1.5rem; line-height: 1.25; font-family: system-ui, sans-serif; }
        .lesson-content { font-size: 1.05rem; }
        .lesson-content h2 { font-family: system-ui, sans-serif; font-size: 1.35rem; margin: 2rem 0 .75rem; color: #111827; }
        .lesson-content h3 { font-family: system-ui, sans-serif; font-size: 1.1rem; margin: 1.5rem 0 .5rem; color: #374151; }
        .lesson-content p { margin: 0 0 1rem; }
        .lesson-content ul, .lesson-content ol { margin: 0 0 1rem; padding-left: 1.5rem; }
        .lesson-content li { margin-bottom: .35rem; }
        .lesson-content blockquote { border-left: 4px solid #22c55e; margin: 1.5rem 0; padding: .75rem 1.25rem; background: #f0fdf4; color: #166534; font-style: italic; }
        .lesson-content strong { color: #111827; }
        .actions { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; font-family: system-ui, sans-serif; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .btn { display: inline-block; padding: .75rem 1.5rem; border-radius: 8px; font-weight: 600; font-size: .9rem; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #22c55e; color: #052e16; }
        .btn-primary:hover { background: #16a34a; }
        .btn-primary:disabled, .btn-done { background: #e5e7eb; color: #6b7280; cursor: default; }
        .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .btn-outline:hover { background: #f3f4f6; }
        .alert { font-family: system-ui, sans-serif; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .88rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="{{ route('dashboard') }}">&larr; Back to Dashboard</a>
        <span>Day {{ $module->day_number }} of 30</span>
    </div>

    <article class="article-wrap">
        @if (session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        <div class="day-label">Day {{ $module->day_number }}{{ $module->is_free ? ' · Free Preview' : '' }}</div>
        <h1>{{ $module->title }}</h1>

        <div class="lesson-content">
            {!! $module->content !!}
        </div>

        <div class="actions">
            @if ($userProgress->completed)
                <span class="btn btn-done">&#10003; Completed</span>
            @else
                <form method="POST" action="{{ route('modules.complete', $module->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Mark as Completed</button>
                </form>
            @endif
            <a href="{{ route('dashboard') }}" class="btn btn-outline">Back to Course</a>
        </div>
    </article>
</body>
</html>
