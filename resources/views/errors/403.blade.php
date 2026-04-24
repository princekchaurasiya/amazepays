@extends('layouts.auth')

@section('title', '403 — Access denied')

@section('content')
<div class="min-h-full px-4 py-12">
    <div class="mx-auto flex max-w-4xl flex-col items-center gap-10 md:flex-row md:items-center md:justify-between md:gap-16">
        <div class="w-full max-w-md text-center md:text-left">
            <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2 text-slate-900 md:mb-10">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-base font-bold text-white">
                    {{ strtoupper(substr(config('app.name', 'A'), 0, 1)) }}
                </span>
                <span class="text-lg font-bold">{{ config('app.name') }}</span>
            </a>
            <p class="text-6xl font-bold tracking-tight text-indigo-600 md:text-7xl">403</p>
            <h1 class="mt-2 text-xl font-semibold text-slate-800 md:text-2xl">Access denied</h1>
            <p class="mt-3 text-slate-600">
                {{ $message ?? 'You do not have permission to view this page.' }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-x-2 gap-y-2 text-sm font-medium md:justify-start">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="auth-link">Go back</a>
                <span class="text-gray-300" aria-hidden="true">&middot;</span>
                <a href="{{ route('home') }}" class="auth-link">Home</a>
                @if(Route::has('panel.dashboard'))
                    <span class="text-gray-300" aria-hidden="true">&middot;</span>
                    <a href="{{ route('panel.dashboard') }}" class="auth-link">Admin dashboard</a>
                @endif
            </div>
            <p class="mt-8 text-xs text-slate-500">
                Need help?
                <a href="mailto:{{ config('companyDefaultValues.company_email', config('mail.from.address')) }}" class="text-indigo-600 hover:underline">
                    {{ config('companyDefaultValues.company_email', config('mail.from.address', 'support@example.com')) }}
                </a>
            </p>
        </div>
        <div class="flex flex-1 justify-center md:justify-end">
            @include('errors.partials.unplugged-illustration', ['class' => 'h-52 w-full max-w-sm text-slate-300 md:h-64'])
        </div>
    </div>
</div>
@endsection
