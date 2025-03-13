@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Accountbeheer</h4>
                    <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nieuw Account
                    </a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Naam</th>
                                    <th>Email</th>
                                    <th>Slimme Meter</th>
                                    <th>Aangemaakt</th>
                                    <th>Laatst bijgewerkt</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($accounts as $account)
                                    <tr>
                                        <td>{{ $account->id }}</td>
                                        <td>{{ $account->name }}</td>
                                        <td>{{ $account->email }}</td>
                                        <td>
                                            @if ($account->smartMeter)
                                                {{ $account->smartMeter->meter_id }}
                                                <span class="badge bg-success">Gekoppeld</span>
                                            @else
                                                <span class="badge bg-warning">Niet gekoppeld</span>
                                            @endif
                                        </td>
                                        <td>{{ $account->created_at->format('d-m-Y H:i') }}</td>
                                        <td>{{ $account->updated_at->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('accounts.show', $account->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $account->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal{{ $account->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $account->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel{{ $account->id }}">Account verwijderen</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Sluiten"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Weet je zeker dat je het account van <strong>{{ $account->name }}</strong> wilt verwijderen? Deze actie kan niet ongedaan gemaakt worden.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                                                            <form action="{{ route('accounts.destroy', $account->id) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">Verwijderen</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Geen accounts gevonden</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $accounts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection