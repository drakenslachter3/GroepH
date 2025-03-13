@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Account details</h4>
                    <div>
                        <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i> Verwijderen
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Status:</div>
                        <div class="col-md-8">
                            @if($account->active)
                                <span class="badge bg-success">Actief</span>
                            @else
                                <span class="badge bg-danger">Inactief</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Rol:</div>
                        <div class="col-md-8">
                            @if($account->role == 'owner')
                                <span class="badge bg-danger">Eigenaar</span>
                            @elseif($account->role == 'admin')
                                <span class="badge bg-warning">Beheerder</span>
                            @else
                                <span class="badge bg-info">Gebruiker</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Naam:</div>
                        <div class="col-md-8">{{ $account->name }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8">{{ $account->email }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Telefoonnummer:</div>
                        <div class="col-md-8">{{ $account->phone ?: 'Niet opgegeven' }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Adres:</div>
                        <div class="col-md-8">{{ $account->address ?: 'Niet opgegeven' }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Postcode:</div>
                        <div class="col-md-8">{{ $account->postal_code ?: 'Niet opgegeven' }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Stad:</div>
                        <div class="col-md-8">{{ $account->city ?: 'Niet opgegeven' }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Slimme Meter:</div>
                        <div class="col-md-8">
                            @if($account->smartMeter)
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success me-2">Gekoppeld</span>
                                    <span>{{ $account->smartMeter->meter_id }} - {{ $account->smartMeter->location }}</span>
                                </div>
                            @else
                                <span class="badge bg-warning">Niet gekoppeld</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Aangemaakt op:</div>
                        <div class="col-md-8">{{ $account->created_at->format('d-m-Y H:i') }}</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 fw-bold">Laatst bijgewerkt:</div>
                        <div class="col-md-8">{{ $account->updated_at->format('d-m-Y H:i') }}</div>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Terug naar overzicht
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Account verwijderen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sluiten"></button>
            </div>
            <div class="modal-body">
                Weet je zeker dat je het account van <strong>{{ $account->name }}</strong> wilt verwijderen? Deze actie kan niet ongedaan gemaakt worden.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                <form action="{{ route('accounts.destroy', $account->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Verwijderen</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection