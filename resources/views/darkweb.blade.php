@extends('layouts.app')

@section('title', 'Dark Web Monitoring - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="row">
                <div class="col-xl-12">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Dark Web Monitoring
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                               @forelse ($breachedEmails as $email)
                                   <x-dark-web.breached-card :employee="$email" :breachDetail="json_decode($email->data, true)" />
                               @empty
                                   <p class="text-center text-muted">No emails have breached.</p>
                               @endforelse
                               

                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />






    {{-- --------------------- modals ---------------------- --}}


    @push('newcss')
    @endpush

    @push('newscripts')
    @endpush

@endsection
