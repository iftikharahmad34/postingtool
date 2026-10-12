@extends('layouts.legal', ['title' => 'Privacy Policy', 'updated' => '12 October 2026'])

@section('content')
    <p>{{ config('app.name') }} is a publishing tool that lets its owner write a post once and publish it to the owner's own Facebook Page, Instagram account, X (Twitter) account and LinkedIn profile. This policy explains what information the tool handles and how it is used. The tool is installed on the owner's own server at <a href="{{ config('app.url') }}" class="font-semibold text-brand-600 dark:text-brand-300">{{ config('app.url') }}</a>; it is not a public service and does not create accounts for visitors.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Information we store</h2>
    <p><b>Sign-in details.</b> The name, e-mail address and a hashed password of the people who operate the tool.</p>
    <p><b>Social media credentials.</b> The access tokens and API keys the owner enters to connect a Facebook Page, Instagram account, X account or LinkedIn profile. They are stored encrypted and are used only to publish the owner's posts and to confirm that the connection works.</p>
    <p><b>Posts.</b> The text and pictures the owner publishes, together with the result of each publication (the link to the published post or the error returned by the network). They are kept so the owner can review their publishing history.</p>
    <p><b>Technical logs.</b> The server may keep ordinary access and error logs, such as the IP address and the time of a request, to keep the tool secure and working.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">How information is used</h2>
    <p>Information is used only to run the tool: to sign the operator in, to publish posts to the networks the owner selected and to show the publishing history. Posts are sent to Meta (Facebook and Instagram), X Corp. and LinkedIn through their official APIs, where they are then governed by those companies' own privacy policies.</p>
    <p>We do not sell, rent or share stored information with anyone else, we do not use it for advertising, and we do not use third-party analytics or tracking.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Cookies</h2>
    <p>The tool sets only the cookies needed to keep the operator signed in and to protect forms against misuse. It also remembers the chosen light or dark theme in the browser.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Data from Facebook and Instagram</h2>
    <p>Through the Meta Graph API the tool reads the name and ID of the owner's Facebook Pages and linked Instagram accounts, and publishes posts to them. No data about Page followers, visitors or other Facebook users is collected or stored.</p>

    <h2 id="delete" class="text-lg font-bold text-slate-900 dark:text-white">Deleting your data</h2>
    <p>Connections to Facebook, Instagram, X and LinkedIn can be removed at any time from the Accounts page of the tool, which deletes the stored credentials. Posts can be deleted from the Posts page. You can also revoke the tool's access from the settings of each social network. To have all remaining data deleted, contact the owner of this installation through the website above.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Security</h2>
    <p>Credentials are encrypted at rest, passwords are hashed, and the tool is served over HTTPS. Only the people with a sign-in to this installation can use it.</p>

    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Changes</h2>
    <p>If this policy changes, the new version is published on this page with an updated date.</p>
@endsection
