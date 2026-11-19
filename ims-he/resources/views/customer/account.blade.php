@extends('layouts.app')
@section('title', 'My Account')
@section('heading', 'My Account')

@section('content')
<div style="max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

    {{-- Status Flash Alert --}}
    @if (session('status'))
        <x-alert type="success" title="Success">
            {{ session('status') }}
        </x-alert>
    @endif

    {{-- Validation Errors Banner --}}
    @if ($errors->any())
        <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 1rem; border-radius: 8px; color: #991b1b;">
            <strong>Please resolve the following issues:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0; font-size: 0.9rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 1. Account Details Overview Card --}}
    <div class="checkout-card" style="margin-bottom: 0;">
        <h3 style="margin-top: 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; font-size: 1.1rem; color: #111827;">
            Account Information
        </h3>

        <table class="cart-table-modern" style="margin-top: 0.5rem;">
            <tbody>
                <tr>
                    <td style="color: #4b5563; font-weight: 600; width: 35%;">Email Address</td>
                    <td style="color: #111827; font-weight: 500;">
                        {{ $customer->email }}
                        @if($customer->hasVerifiedEmail())
                            <span class="status status-completed" style="font-size: 0.7rem; margin-left: 0.5rem;">Verified</span>
                        @else
                            <span class="status status-pending" style="font-size: 0.7rem; margin-left: 0.5rem;">Unverified</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="color: #4b5563; font-weight: 600;">Member Since</td>
                    <td style="color: #111827; font-weight: 500;">
                        {{ $customer->created_at ? $customer->created_at->format('M j, Y') : 'N/A' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- 2. Edit Profile Form Card --}}
    <div class="checkout-card" style="margin-bottom: 0;">
        <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; margin-bottom: 1rem;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #111827;">Personal Details & Address</h3>
            <p style="margin: 0.35rem 0 0 0; font-size: 0.85rem; color: #6b7280;">
                These details will automatically pre-fill when placing orders at checkout.
            </p>
        </div>

        <form method="POST" action="{{ route('customer.account.update') }}">
            @csrf
            @method('PATCH')

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                {{-- First Name --}}
                <div>
                    <label for="first_name" style="font-weight: 600; font-size: 0.875rem; display: block; margin-bottom: 0.35rem; color: #374151;">
                        First Name
                    </label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $customer->first_name) }}" maxlength="50" autocomplete="given-name"
                        style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.95rem; box-sizing: border-box;">
                </div>

                {{-- Last Name --}}
                <div>
                    <label for="last_name" style="font-weight: 600; font-size: 0.875rem; display: block; margin-bottom: 0.35rem; color: #374151;">
                        Last Name
                    </label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $customer->last_name) }}" maxlength="50" autocomplete="family-name"
                        style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.95rem; box-sizing: border-box;">
                </div>

                {{-- Contact Number --}}
                <div style="grid-column: 1 / -1;">
                    <label for="contact_number" style="font-weight: 600; font-size: 0.875rem; display: block; margin-bottom: 0.35rem; color: #374151;">
                        Contact Number
                    </label>
                    <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number', $customer->contact_number) }}" maxlength="14" inputmode="numeric" pattern="[0-9]*" title="Digits only" placeholder="e.g., 09171234567" autocomplete="tel"
                        style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.95rem; box-sizing: border-box;">
                </div>

                {{-- Delivery Address --}}
                <div style="grid-column: 1 / -1;">
                    <label for="address" style="font-weight: 600; font-size: 0.875rem; display: block; margin-bottom: 0.35rem; color: #374151;">
                        Default Delivery Address
                    </label>
                    <input type="text" id="address" name="address" value="{{ old('address', $customer->address) }}" maxlength="100" placeholder="Street, Barangay, City, Province" autocomplete="street-address"
                        style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.95rem; box-sizing: border-box;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="padding: 0.75rem 1.5rem; font-size: 0.95rem;">
                Save Profile Changes
            </button>
        </form>
    </div>

</div>
@endsection