<div class="page">
    <div class="page-header">
        <div class="page-title">Clients</div>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">+ New client</a>
    </div>
    <input wire:model.live.debounce.300ms="search" placeholder="Search clients" class="field mb-3 max-w-sm">
    <div class="card overflow-x-auto">
        <table class="data-table">
            <colgroup>
                <col>
                <col>
                <col style="width: 5.5rem">
                <col style="width: 5rem">
            </colgroup>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Currency</th>
                    <th class="right">Invoices</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr class="is-link" onclick="window.location='{{ route('clients.show', $client) }}'">
                        <td>
                            <div class="strong truncate">{{ $client->company }}</div>
                            <div class="subtle truncate">{{ $client->contact }}</div>
                        </td>
                        <td class="muted truncate">{{ $client->email }}</td>
                        <td>{{ $client->default_currency }}</td>
                        <td class="num right">{{ $client->invoices_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="data-table-empty">No clients yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $clients->links() }}</div>
</div>
