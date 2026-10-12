<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop stop-color='%237444ff'/%3E%3Cstop offset='1' stop-color='%23d62976'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='9' fill='url(%23g)'/%3E%3Cpath d='M9 16.5 23 9l-4.5 15-3.5-6.2L9 16.5Z' fill='white'/%3E%3C/svg%3E">
<script>
    // Apply the saved theme before paint to avoid a flash.
    (() => {
        const t = localStorage.getItem('theme');
        if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
    })();
</script>
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
