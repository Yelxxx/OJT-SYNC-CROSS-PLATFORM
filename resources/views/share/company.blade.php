<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $company->name }} — OJT Opportunities</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif; background: #f6f4ee; color: #1f2937; min-height: 100vh; }
        .header { background: #1d4ed8; color: #fff; padding: 3rem 1.5rem; text-align: center; }
        .header h1 { font-size: 1.75rem; font-weight: 700; }
        .header p { color: #bfdbfe; margin-top: .5rem; font-size: .95rem; }
        .logo { width: 84px; height: 84px; border-radius: 16px; background: #fff; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; color: #1d4ed8; overflow: hidden; }
        .logo img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
        .meta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1rem; font-size: .85rem; color: #dbeafe; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
        .about { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 1.5rem; font-size: .95rem; line-height: 1.6; color: #4b5563; margin-bottom: 2rem; }
        h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; }
        .job { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .job h3 { font-size: 1rem; font-weight: 600; }
        .job p { font-size: .85rem; color: #6b7280; margin-top: .35rem; line-height: 1.5; }
        .badge { flex-shrink: 0; background: #dbeafe; color: #1e40af; font-size: .72rem; font-weight: 700; padding: .35rem .7rem; border-radius: 999px; white-space: nowrap; }
        .empty { text-align: center; color: #9ca3af; padding: 3rem 0; font-size: .95rem; }
        .footer { text-align: center; font-size: .8rem; color: #9ca3af; padding-bottom: 3rem; }
        .footer a { color: #1d4ed8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if ($company->logo_path)
                <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }}">
            @else
                {{ mb_substr($company->name, 0, 1) }}
            @endif
        </div>
        <h1>{{ $company->name }}</h1>
        <p>Internship (OJT) opportunities</p>
        <div class="meta">
            @if ($company->address)<span class="meta-item"><x-icon name="office-building" style="width:1rem;height:1rem;vertical-align:-0.2rem" /> {{ $company->address }}</span>@endif
            @if ($company->website)<span class="meta-item"><x-icon name="link" style="width:1rem;height:1rem;vertical-align:-0.2rem" /> {{ $company->website }}</span>@endif
        </div>
    </div>

    <div class="wrap">
        @if ($company->description)
            <div class="about">{{ $company->description }}</div>
        @endif

        <h2>Open positions ({{ $company->jobPostings->count() }})</h2>

        @if ($company->jobPostings->isEmpty())
            <div class="empty">No open positions right now — check back soon!</div>
        @else
            @foreach ($company->jobPostings as $job)
                <div class="job">
                    <div>
                        <h3>{{ $job->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($job->description), 140) }}</p>
                    </div>
                    <span class="badge">{{ $job->slots }} slot{{ $job->slots > 1 ? 's' : '' }}</span>
                </div>
            @endforeach
            <p class="footer" style="margin-top:2rem">Interested? Sign up as a student and apply through
                <a href="{{ url('/') }}">{{ config('app.name', 'OJT Sync') }}</a>.
            </p>
        @endif

        <p class="footer">Powered by <a href="{{ url('/') }}">{{ config('app.name', 'OJT Sync') }}</a></p>
    </div>
</body>
</html>
