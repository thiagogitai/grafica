@php
    use Illuminate\Support\Facades\File;

    $manifestPath = public_path('build/manifest.json');
    $viteManifest = File::exists($manifestPath) ? json_decode(File::get($manifestPath), true) : null;
@endphp

@if ($viteManifest && isset($viteManifest['resources/js/app.js']))
    @php
        $jsEntry = $viteManifest['resources/js/app.js']['file'] ?? null;
        $cssFromJs = $viteManifest['resources/js/app.js']['css'] ?? [];
        $cssEntry = $viteManifest['resources/css/app.css']['file'] ?? null;
    @endphp

    @if ($cssEntry)
        <link rel="stylesheet" href="{{ asset('build/' . $cssEntry) }}">
    @endif

    @foreach ($cssFromJs as $cssFile)
        <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
    @endforeach

    @if ($jsEntry)
        <script type="module" src="{{ asset('build/' . $jsEntry) }}" defer></script>
    @endif
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
