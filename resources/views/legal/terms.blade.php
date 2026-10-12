@extends('layouts.legal', ['title' => 'Terms of Service', 'updated' => '12 October 2026'])

@section('content')
    <p>These terms apply to the use of {{ config('app.name') }}, a self-hosted tool installed at <a href="{{ config('app.url') }}" class="font-semibold text-brand-600 dark:text-brand-300">{{ config('app.url') }}</a> that publishes posts to the owner's own social media accounts. By using the tool you agree to these terms.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Who may use the tool</h2>
    <p>The tool is for the owner of this installation and the people the owner invites. It is not a public service and does not accept sign-ups from visitors.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Your accounts and content</h2>
    <p>You may only connect social media accounts that you own or are authorised to manage. You are responsible for everything you publish through the tool and must follow the terms and community rules of Facebook, Instagram, X and LinkedIn. Do not use the tool to post spam, unlawful content or content that infringes someone else's rights.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Third-party services</h2>
    <p>Publishing depends on the APIs of Meta, X Corp. and LinkedIn. Those companies may change, limit or withdraw their APIs at any time, and a post can fail or be shown differently for reasons outside the tool's control.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">No warranty</h2>
    <p>The tool is provided "as is", without any warranty. To the extent permitted by law, the owner of this installation is not liable for lost posts, failed publications or any damage arising from the use of the tool.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Privacy</h2>
    <p>How information is handled is described in the <a href="{{ route('privacy') }}" class="font-semibold text-brand-600 dark:text-brand-300">Privacy Policy</a>.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Changes</h2>
    <p>These terms may be updated from time to time. The current version is always published on this page.</p>
@endsection
