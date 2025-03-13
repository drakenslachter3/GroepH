@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ isset($account) ? 'Account bewerken' : 'Nieuw account aanmaken' }}</h4>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ isset($account) ? route('accounts.update', $account->id) : route('accounts.store') }}">
                        @csrf
                        @if(isset($account))
                            @method('PUT')
                        @endif

                        <div class="mb-3">
                            <label for="name" class="form-label">Naam <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', isset($account) ? $account->name : '') }}" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', isset($account) ? $account->email : '') }}" required>
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Wachtwoord {{ isset($account) ? '' : '<span class="text-danger">*</span>' }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" {{ isset($account) ? '' : 'required' }}>
                            @if(isset($account))
                                <small class="form-text text-muted">Laat leeg om het huidige wachtwoord te behouden</small>
                            @endif
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Bevestig wachtwoord {{ isset($account) ? '' : '<span class="text-danger">*</span>' }}</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" {{ isset($account) ? '' : 'required' }}>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefoonnummer</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', isset($account) ? $account->phone : '') }}">
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Adres</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', isset($account) ? $account->address : '') }}">
                            @error('address')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Postcode</label>
                            <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code', isset($account) ? $account->postal_code : '') }}">
                            @error('postal_code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="city" class="form-label">Stad</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', isset($account) ? $account->city : '') }}">
                            @error('city')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="smart_meter_id" class="form-label">Slimme Meter ID</label>
                            <select class="form-select @error('smart_meter_id') is-invalid @enderror" id="smart_meter_id" name="smart_meter_id">
                                <option value="">-- Selecteer een slimme meter --</option>
                                @foreach($smartMeters as $meter)
                                    <option value="{{ $meter->id }}" {{ (old('smart_meter_id', isset($account) && $account->smartMeter ? $account->smartMeter->id : '') == $meter->id) ? 'selected' : '' }}>
                                        {{ $meter->meter_id }} - {{ $meter->location }}
                                    </option>
                                @endforeach
                            </select>
                            @error('smart_meter_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="user" {{ (old('role', isset($account) ? $account->role : '') == 'user') ? 'selected' : '' }}>Gebruiker</option>
                                <option value="admin" {{ (old('role', isset($account) ? $account->role : '') == 'admin') ? 'selected' : '' }}>Beheerder</option>
                                <option value="owner" {{ (old('role', isset($account) ? $account->role : '') == 'owner') ? 'selected' : '' }}>Eigenaar</option>
                            </select>
                            @error('role')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input @error('active') is-invalid @enderror" type="checkbox" id="active" name="active" value="1" {{ old('active', isset($account) ? $account->active : '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    Account actief
                                </label>
                                @error('active')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Annuleren</a>
                            <button type="submit" class="btn btn-primary">
                                {{ isset($account) ? 'Wijzigingen opslaan' : 'Account aanmaken' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection