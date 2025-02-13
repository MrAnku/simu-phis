@extends('layouts.app')

@section('title', 'Config Required - Phishing awareness training program')

@section('main-content')

    <div class="main-content app-content">
        <div class="container-fluid mt-4">

            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-6">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                Please enter your WhatsApp Cloud API Details
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{route('whatsapp.saveconfig')}}" method="post">
                                @csrf
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="from_phone_id" id="phone_id" placeholder="Phone Number ID">
                                    <label for="phone_id">From Phone Number ID</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="access_token" name="access_token" placeholder="Access Token">
                                    <label for="access_token">Access Token</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="business_id" name="business_id" placeholder="Business ID">
                                    <label for="business_id">Business ID</label>
                                </div>
                                <div>
                                    <button type="submit" class="btn w-100 btn-primary btn-wave">Submit</button>
                                </div>
                            </form>
                            
                        </div>
                    </div>
                    
                </div>
                <div class="col-md-3"></div>
            </div>

         

        </div>
    </div>

   


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



@endsection
