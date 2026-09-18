<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Learning Dashboard — Forex Academy</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0b1120; color: #e2e8f0; margin: 0; line-height: 1.5; }
        header { background: #111827; border-bottom: 1px solid #1f2937; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; }
        header .brand { font-weight: 700; font-size: 1.1rem; }
        .badge { display: inline-block; padding: .2rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; margin-left: .5rem; vertical-align: middle; }
        .badge.free { background: #14532d; color: #86efac; }
        .badge.paid_full { background: #1e3a8a; color: #93c5fd; }
        .badge.paid_daily { background: #581c87; color: #d8b4fe; }
        .header-actions { display: flex; align-items: center; gap: 1rem; font-size: .9rem; color: #94a3b8; }
        .nav-link { color: #94a3b8; text-decoration: none; }
        .nav-link:hover { color: #fff; }
        button.link { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: inherit; }
        button.link:hover { color: #fff; }
        main { max-width: 1100px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        .hero { margin-bottom: 2rem; }
        .hero h1 { margin: 0 0 .35rem; font-size: 1.75rem; }
        .hero p { margin: 0; color: #94a3b8; }
        .stats { display: flex; gap: 1.5rem; margin-top: 1.25rem; flex-wrap: wrap; }
        .stat { background: #111827; border: 1px solid #1f2937; border-radius: 10px; padding: .85rem 1.1rem; min-width: 120px; }
        .stat strong { display: block; font-size: 1.25rem; color: #fff; }
        .stat span { font-size: .8rem; color: #64748b; }
        .alert { padding: .85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .9rem; }
        .alert.error { background: #450a0a; color: #fecaca; border: 1px solid #7f1d1d; }
        .alert.success { background: #14532d; color: #bbf7d0; border: 1px solid #166534; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 12px; overflow: hidden; transition: transform .15s, border-color .15s, box-shadow .15s; position: relative; }
        .card.unlocked { cursor: pointer; text-decoration: none; color: inherit; display: block; }
        .card.unlocked:hover { transform: translateY(-3px); border-color: #22c55e; box-shadow: 0 8px 24px rgba(34,197,94,.12); }
        .card.locked { cursor: pointer; opacity: .75; }
        .card.locked:hover { border-color: #475569; }
        .card-thumb { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); height: 100px; display: flex; align-items: center; justify-content: center; position: relative; }
        .card-thumb .day { font-size: 2rem; font-weight: 800; color: #334155; }
        .card.unlocked .card-thumb .day { color: #22c55e; }
        .card-badge { position: absolute; top: .6rem; left: .6rem; font-size: .65rem; font-weight: 700; padding: .2rem .45rem; border-radius: 4px; text-transform: uppercase; }
        .card-badge.free-tag { background: #166534; color: #bbf7d0; }
        .card-badge.done-tag { background: #1e3a8a; color: #bfdbfe; top: auto; bottom: .6rem; right: .6rem; left: auto; }
        .lock-overlay { position: absolute; inset: 0; background: rgba(11,17,32,.55); display: flex; align-items: center; justify-content: center; }
        .lock-icon { width: 36px; height: 36px; background: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #475569; font-size: 1rem; }
        .card-body { padding: .9rem 1rem 1.1rem; }
        .card-body h3 { margin: 0 0 .35rem; font-size: .92rem; font-weight: 600; line-height: 1.35; color: #f1f5f9; }
        .card-body p { margin: 0; font-size: .78rem; color: #64748b; }
        .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 100; align-items: center; justify-content: center; padding: 1rem; }
        .modal-backdrop.open { display: flex; }
        .modal { background: #111827; border: 1px solid #1f2937; border-radius: 14px; max-width: 440px; width: 100%; padding: 1.75rem; position: relative; }
        .modal h2 { margin: 0 0 .5rem; font-size: 1.25rem; }
        .modal .sub { color: #94a3b8; font-size: .88rem; margin-bottom: 1.25rem; }
        .plan { border: 1px solid #334155; border-radius: 10px; padding: 1rem; margin-bottom: .75rem; cursor: pointer; transition: border-color .15s; }
        .plan:hover, .plan.selected { border-color: #22c55e; background: rgba(34,197,94,.05); }
        .plan h4 { margin: 0 0 .25rem; font-size: .95rem; }
        .plan .price { font-size: 1.35rem; font-weight: 700; color: #22c55e; }
        .plan p { margin: .35rem 0 0; font-size: .8rem; color: #64748b; }
        .modal-close { position: absolute; top: .85rem; right: 1rem; background: none; border: none; color: #64748b; font-size: 1.4rem; cursor: pointer; line-height: 1; }
        .modal-close:hover { color: #fff; }
        .btn-pay { width: 100%; padding: .85rem; margin-top: .5rem; border: none; border-radius: 8px; background: #22c55e; color: #052e16; font-weight: 700; font-size: .95rem; cursor: pointer; }
        .btn-pay:disabled { opacity: .5; cursor: not-allowed; }
        .pay-status { margin-top: .75rem; font-size: .85rem; text-align: center; min-height: 1.25rem; }
        .pay-status.error { color: #f87171; }
        .pay-status.success { color: #86efac; }
    </style>
</head>
<body>
    <header>
        <div>
            <span class="brand">Forex Academy</span>
            <span class="badge {{ $subscriptionStatus }}">{{ str_replace('_', ' ', ucfirst($subscriptionStatus)) }}</span>
        </div>
        <div class="header-actions">
            <span>{{ $user->name }}</span>
            <a href="{{ route('profile.edit') }}" class="nav-link">Profile</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button class="link" type="submit">Logout</button>
            </form>
        </div>
    </header>

    <main>
        <div class="hero">
            <h1>30-Day Beginner Forex Course</h1>
            <p>Master the fundamentals day by day. Days 1–3 are free — unlock the full curriculum when you're ready.</p>
            <div class="stats">
                <div class="stat">
                    <strong>{{ $progress->filter()->count() }}</strong>
                    <span>Completed</span>
                </div>
                <div class="stat">
                    <strong>{{ $accessibleModules->count() }}</strong>
                    <span>Unlocked</span>
                </div>
                <div class="stat">
                    <strong>30</strong>
                    <span>Total Lessons</span>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        <div class="grid">
            @foreach ($modules as $module)
                @php
                    $accessible = $accessibleModules->contains('id', $module->id);
                    $completed = (bool) $progress->get($module->id, false);
                @endphp

                @if ($accessible)
                    <a href="{{ route('modules.show', $module->id) }}" class="card unlocked">
                        <div class="card-thumb">
                            @if ($module->is_free)
                                <span class="card-badge free-tag">Free</span>
                            @endif
                            @if ($completed)
                                <span class="card-badge done-tag">Done</span>
                            @endif
                            <span class="day">{{ $module->day_number }}</span>
                        </div>
                        <div class="card-body">
                            <h3>Day {{ $module->day_number }}: {{ $module->title }}</h3>
                            <p>{{ $completed ? 'Completed — review lesson' : 'Click to start lesson' }}</p>
                        </div>
                    </a>
                @else
                    <div class="card locked" data-locked-module="{{ $module->day_number }}" onclick="openPaymentModal({{ $module->day_number }}, '{{ addslashes($module->title) }}')">
                        <div class="card-thumb">
                            <span class="day">{{ $module->day_number }}</span>
                            <div class="lock-overlay">
                                <span class="lock-icon">&#128274;</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3>Day {{ $module->day_number }}: {{ $module->title }}</h3>
                            <p>Locked — tap to unlock</p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </main>

    <div class="modal-backdrop" id="paymentModal">
        <div class="modal">
            <button class="modal-close" type="button" onclick="closePaymentModal()">&times;</button>
            <h2>Unlock This Lesson</h2>
            <p class="sub" id="modalModuleLabel">Choose a plan to continue your Forex journey.</p>

            <div class="plan selected" data-type="full_100" onclick="selectPlan(this)">
                <h4>Full Course — All 30 Days</h4>
                <div class="price">${{ number_format($pricing['course_price_full'], 0) }}</div>
                <p>One-time payment. Lifetime access to every module.</p>
            </div>

            <div class="plan" data-type="daily_4" onclick="selectPlan(this)">
                <h4>Daily Plan — Pay Per Day</h4>
                <div class="price">${{ number_format($pricing['course_price_daily'], 0) }}/day</div>
                <p>Unlock the next lesson as you progress, one day at a time.</p>
            </div>

            <button class="btn-pay" id="payBtn" type="button" onclick="initiatePayment()">Pay with Mobile Money</button>
            <div class="pay-status" id="payStatus"></div>
        </div>
    </div>

    <script>
        let selectedPlan = 'full_100';

        function openPaymentModal(dayNumber, title) {
            document.getElementById('modalModuleLabel').textContent =
                'Day ' + dayNumber + ': ' + title + ' — choose a plan to unlock.';
            document.getElementById('paymentModal').classList.add('open');
            document.getElementById('payStatus').textContent = '';
            document.getElementById('payStatus').className = 'pay-status';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('open');
        }

        function selectPlan(el) {
            document.querySelectorAll('.plan').forEach(p => p.classList.remove('selected'));
            el.classList.add('selected');
            selectedPlan = el.dataset.type;
        }

        async function initiatePayment() {
            const btn = document.getElementById('payBtn');
            const status = document.getElementById('payStatus');
            btn.disabled = true;
            status.textContent = 'Sending payment request to your phone…';
            status.className = 'pay-status';

            try {
                const res = await fetch('{{ route('payments.initiate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: selectedPlan }),
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Payment failed. Please try again.');
                }

                status.textContent = data.message || 'Check your phone to approve the payment.';
                status.className = 'pay-status success';
            } catch (err) {
                status.textContent = err.message;
                status.className = 'pay-status error';
            } finally {
                btn.disabled = false;
            }
        }

        document.getElementById('paymentModal').addEventListener('click', function (e) {
            if (e.target === this) closePaymentModal();
        });
    </script>
</body>
</html>
